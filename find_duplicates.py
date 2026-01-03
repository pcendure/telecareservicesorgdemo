
import os
import re
from collections import defaultdict

base_dir = r"C:\Users\ECAPI FORENSICS\Documents\2026AI\TODAY"
files_to_check = [
    "index.html",
    "who-we-are/index.html",
    "services/index.html",
    "career/index.html",
    "contact-us/index.html",
    "es/index.html",
    "es/who-we-are/index.html",
    "es/services/index.html",
    "es/career/index.html",
    "es/contact-us/index.html"
]

# Files to ignore (logos, icons, etc that SHOULD be duplicated)
ignored_images = {
    "T1.png",
    "telecare-logo-transparent.png",
    "PHOTO-2023-11-14-16-41-58.jpg"
}

image_usage = defaultdict(list)

def resolve_filename(path):
    return os.path.basename(path)

print("Scanning for image usage...")

for relative_path in files_to_check:
    file_path = os.path.join(base_dir, relative_path)
    if not os.path.exists(file_path):
        continue
        
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
        
    # Find img tags
    img_matches = re.findall(r'<img[^>]+src=["\']([^"\']+)["\']', content)
    # Find background images
    bg_matches = re.findall(r'background-image:\s*url\(["\']?([^"\')]+)["\']?\)', content)
    
    all_images = img_matches + bg_matches
    
    for img_src in all_images:
        filename = resolve_filename(img_src)
        if filename in ignored_images:
            continue
            
        image_usage[filename].append(relative_path)

print("\n--- Duplicate Image Report ---")
for filename, pages in image_usage.items():
    if len(pages) > 1:
        print(f"\nImage: {filename}")
        print(f"Used {len(pages)} times in:")
        for page in pages:
            print(f"  - {page}")

print("\n--- Per-Page Duplicates ---")
for relative_path in files_to_check:
    file_path = os.path.join(base_dir, relative_path)
    if not os.path.exists(file_path): 
        continue
        
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
        
    page_images = []
    img_matches = re.findall(r'(?:src|url\(["\']?)([^"\')]+)(?:["\']?\))', content)
    
    # Filter for image extensions only to avoid js/css links
    img_matches = [m for m in img_matches if m.lower().endswith(('.png', '.jpg', '.jpeg', '.gif', '.webp'))]
    
    seen = set()
    dupes = set()
    for img in img_matches:
        filename = resolve_filename(img)
        if filename in ignored_images: continue
        
        if filename in seen:
            dupes.add(filename)
        seen.add(filename)
        
    if dupes:
        print(f"\nPage: {relative_path} has duplicates:")
        for d in dupes:
            print(f"  - {d}")
