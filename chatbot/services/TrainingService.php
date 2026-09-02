<?php
/**
 * 🤖 CampusAI — Q&A Document Training & Ingestion Service
 * Ingests PDF/DOCX Q&A guides, extracts questions & answers,
 * and publishes them directly into the Knowledge Base & FAQ databases.
 */

class TrainingService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Publish the Master QnA Guide PDF/JSON into the Chatbot Database
     */
    public function publishMasterGuide(): array
    {
        $jsonFile = __DIR__ . '/../train/qna_dataset.json';
        $pdfFile = __DIR__ . '/../train/CampusAI_Chatbot_QnA_Master_Guide.pdf';

        if (!file_exists($jsonFile)) {
            return [
                'success' => false,
                'message' => 'Master training dataset file (qna_dataset.json) not found.'
            ];
        }

        $data = json_decode(file_get_contents($jsonFile), true);
        if (!$data || empty($data['sections'])) {
            return [
                'success' => false,
                'message' => 'Invalid or empty training dataset JSON.'
            ];
        }

        $faqInserted = 0;
        $kbInserted = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($data['sections'] as $sec) {
            $cat = $sec['category'] ?? 'general';

            foreach ($sec['items'] as $item) {
                $q = trim($item['question']);
                $a = trim($item['answer']);

                if (empty($q) || empty($a)) continue;

                // 1. Ingest/Update FAQ
                $existingFaq = $this->db->fetchOne("SELECT id FROM faq WHERE question = ?", [$q]);
                if ($existingFaq) {
                    $this->db->update('faq', [
                        'answer' => $a,
                        'category' => $cat,
                        'status' => 'active'
                    ], 'id = ?', [$existingFaq['id']]);
                } else {
                    $this->db->insert('faq', [
                        'question' => $q,
                        'answer' => $a,
                        'category' => $cat,
                        'status' => 'active',
                        'created_at' => $now
                    ]);
                    $faqInserted++;
                }

                // 2. Ingest/Update Knowledge Base
                $existingKb = $this->db->fetchOne("SELECT id FROM knowledge_base WHERE title = ?", [$q]);
                if ($existingKb) {
                    $this->db->update('knowledge_base', [
                        'content' => $a,
                        'category' => $cat,
                        'keywords' => mb_strtolower($q . ' ' . $cat),
                        'status' => 'active'
                    ], 'id = ?', [$existingKb['id']]);
                } else {
                    $this->db->insert('knowledge_base', [
                        'title' => $q,
                        'category' => $cat,
                        'content' => $a,
                        'keywords' => mb_strtolower($q . ' ' . $cat),
                        'status' => 'active',
                        'created_at' => $now
                    ]);
                    $kbInserted++;
                }
            }
        }

        // Record in uploaded_documents
        $docSize = file_exists($pdfFile) ? filesize($pdfFile) : 18131;
        $existingDoc = $this->db->fetchOne("SELECT id FROM uploaded_documents WHERE original_name = 'CampusAI_Chatbot_QnA_Master_Guide.pdf'");
        if ($existingDoc) {
            $this->db->update('uploaded_documents', [
                'status' => 'published',
                'file_size' => $docSize,
                'extracted_text' => "Published {$data['total_canonical_questions']} canonical Q&As into Knowledge Base & FAQs."
            ], 'id = ?', [$existingDoc['id']]);
        } else {
            $this->db->insert('uploaded_documents', [
                'filename' => 'CampusAI_Chatbot_QnA_Master_Guide.pdf',
                'original_name' => 'CampusAI_Chatbot_QnA_Master_Guide.pdf',
                'file_type' => 'pdf',
                'file_size' => $docSize,
                'extracted_text' => "Published {$data['total_canonical_questions']} canonical Q&As into Knowledge Base & FAQs.",
                'status' => 'published'
            ]);
        }

        return [
            'success' => true,
            'total_qna' => $data['total_canonical_questions'] ?? 63,
            'faq_inserted' => $faqInserted,
            'kb_inserted' => $kbInserted,
            'message' => "Successfully published {$data['total_canonical_questions']} Q&As from CampusAI_Chatbot_QnA_Master_Guide.pdf into the Chatbot Database!"
        ];
    }

    /**
     * Parse and import any uploaded Q&A PDF or DOCX file
     */
    public function importUploadedQnADocument(string $filePath, string $originalName, string $category = 'general'): array
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $extractedText = "";

        // Extract text using Python PyPDF or python-docx for high accuracy
        $escapedPath = escapeshellarg($filePath);
        if ($ext === 'pdf') {
            $cmd = "python -c \"import pypdf; reader=pypdf.PdfReader({$escapedPath}); print('\\n'.join([p.extract_text() for p in reader.pages]))\"";
            $extractedText = shell_exec($cmd) ?: '';
        } elseif ($ext === 'docx') {
            $cmd = "python -c \"import docx; doc=docx.Document({$escapedPath}); print('\\n'.join([p.text for p in doc.paragraphs if p.text.strip()]))\"";
            $extractedText = shell_exec($cmd) ?: '';
        } else {
            $extractedText = file_get_contents($filePath) ?: '';
        }

        if (empty(trim($extractedText))) {
            return [
                'success' => false,
                'message' => 'Could not extract text from document.'
            ];
        }

        // Parse Q&A pairs: Look for patterns like "Q: ... Answer: ..." or "Q1: ... Answer: ..."
        $qnaPairs = [];
        $pattern = '/(?:Q\d*|Question\s*\d*)[\s:\]\.\-]+(.*?)(?:Answer|Ans)[\s:\]\.\-]+(.*?)(?=(?:Q\d*|Question\s*\d*)[\s:\]\.\-|\Z)/si';

        if (preg_match_all($pattern, $extractedText, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $q = trim($m[1]);
                $a = trim($m[2]);
                // Clean up trailing badges or brackets
                $q = preg_replace('/^\[.*?\]\s*/', '', $q);
                if (!empty($q) && !empty($a)) {
                    $qnaPairs[] = ['question' => $q, 'answer' => $a];
                }
            }
        }

        $now = date('Y-m-d H:i:s');
        $importedCount = 0;

        if (!empty($qnaPairs)) {
            // Structured Q&A Document detected
            foreach ($qnaPairs as $item) {
                $q = $item['question'];
                $a = $item['answer'];

                $existingFaq = $this->db->fetchOne("SELECT id FROM faq WHERE question = ?", [$q]);
                if ($existingFaq) {
                    $this->db->update('faq', ['answer' => $a, 'category' => $category, 'status' => 'active'], 'id = ?', [$existingFaq['id']]);
                } else {
                    $this->db->insert('faq', ['question' => $q, 'answer' => $a, 'category' => $category, 'status' => 'active', 'created_at' => $now]);
                }

                $existingKb = $this->db->fetchOne("SELECT id FROM knowledge_base WHERE title = ?", [$q]);
                if ($existingKb) {
                    $this->db->update('knowledge_base', ['content' => $a, 'category' => $category, 'keywords' => mb_strtolower($q . ' ' . $category), 'status' => 'active'], 'id = ?', [$existingKb['id']]);
                } else {
                    $this->db->insert('knowledge_base', ['title' => $q, 'category' => $category, 'content' => $a, 'keywords' => mb_strtolower($q . ' ' . $category), 'status' => 'active', 'created_at' => $now]);
                }
                $importedCount++;
            }
        }

        // Store in uploaded_documents table
        $docId = $this->db->insert('uploaded_documents', [
            'filename' => basename($filePath),
            'original_name' => $originalName,
            'file_type' => $ext,
            'file_size' => filesize($filePath),
            'extracted_text' => $extractedText,
            'status' => !empty($qnaPairs) ? 'published' : 'extracted'
        ]);

        return [
            'success' => true,
            'doc_id' => $docId,
            'is_qna_document' => !empty($qnaPairs),
            'qna_count' => count($qnaPairs),
            'imported_count' => $importedCount,
            'extracted_snippet' => mb_substr($extractedText, 0, 300)
        ];
    }
}
