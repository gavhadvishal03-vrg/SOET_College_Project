import os
import fitz # pymupdf
from PIL import Image
import io

downloads = r"C:\Users\morer\Downloads"
extract_dir = r"c:\xampp\htdocs\project\scratch\extracted_media"
os.makedirs(extract_dir, exist_ok=True)

files = [
    "SOET ADMISSION BROCHURE.pdf",
    "SOET LEAFLET.pdf",
    "INTEGRATED DATA SCIENCE_22-28 Batch_BOOKLET.pdf",
    "INTEGRATED ICBT_22-28 Batch_BOOKLET.pdf",
    "B.Tech Electronics Engineering (VLSI Design and Technology) Booklet.pdf",
    "REGULAR_CSE_CYBERSECURITY_BOOKLET.pdf",
    "INTEGRATED_COMMON TO ALL_2025 BATCH(1ST TO 4TH SEM).pdf",
]

for filename in files:
    pdf_path = os.path.join(downloads, filename)
    if not os.path.exists(pdf_path):
        continue
    doc = fitz.open(pdf_path)
    base_name = os.path.splitext(filename)[0].replace(" ", "_")
    folder = os.path.join(extract_dir, base_name)
    os.makedirs(folder, exist_ok=True)
    
    print(f"\nProcessing {filename} ({len(doc)} pages)...")
    
    # Render brochure/leaflet all pages, for others render first 10 pages
    max_render = len(doc) if "BROCHURE" in filename or "LEAFLET" in filename else min(10, len(doc))
    for page_idx in range(max_render):
        page = doc[page_idx]
        pix = page.get_pixmap(dpi=150)
        page_img_path = os.path.join(folder, f"page_{page_idx+1}.png")
        pix.save(page_img_path)
        
    # Extract embedded images from key pages or all
    extracted_xrefs = set()
    for page_idx in range(len(doc)):
        page = doc[page_idx]
        image_list = page.get_images(full=True)
        for img_idx, img in enumerate(image_list):
            xref = img[0]
            if xref in extracted_xrefs:
                continue
            extracted_xrefs.add(xref)
            base_image = doc.extract_image(xref)
            image_bytes = base_image["image"]
            image_ext = base_image["ext"]
            # skip tiny 1x1 or trivial icons if any
            if len(image_bytes) > 2000:
                img_path = os.path.join(folder, f"p{page_idx+1}_img{img_idx+1}_{xref}.{image_ext}")
                with open(img_path, "wb") as f_img:
                    f_img.write(image_bytes)
                
    print(f"Rendered & extracted to {folder} (Images found: {len(extracted_xrefs)})")
