
import os
import re
from pathlib import Path
from urllib.parse import unquote

# Configuration
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

def check_file_exists(base_path, relative_url):
    # Ignore external links, anchors, mailto, tel
    if relative_url.startswith(('http', 'https', '#', 'mailto:', 'tel:', 'javascript:')):
        return True
    
    # Remove query params and anchors for file check
    clean_url = relative_url.split('?')[0].split('#')[0]
    
    # Handle absolute paths (relative to site root if they start with /) - assuming site root is base_dir
    if clean_url.startswith('/'):
        target_path = os.path.join(base_dir, clean_url.lstrip('/'))
    else:
        target_path = os.path.join(os.path.dirname(base_path), clean_url)
        
    return os.path.exists(target_path)

def validate_links():
    errors = []
    
    for relative_path in files_to_check:
        file_path = os.path.join(base_dir, relative_path)
        if not os.path.exists(file_path):
            errors.append(f"CRITICAL: Main file missing: {file_path}")
            continue
            
        print(f"Checking {relative_path}...")
        
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
                
            # Find all src and href attributes
            # Regex is simple and might miss edge cases but good for a quick check of standard static sites
            links = re.findall(r'(?:src|href)=["\']([^"\']+)["\']', content)
            
            for link in links:
                if not check_file_exists(file_path, link):
                    errors.append(f"[BROKEN LINK] In {relative_path}: {link}")
                    
            # Check specifically for T1.png usage
            if "T1.png" in content:
                # verify the path specifically
                match = re.search(r'src=["\']([^"\']*T1\.png)["\']', content)
                if match:
                    img_path = match.group(1)
                    if not check_file_exists(file_path, img_path):
                         errors.append(f"[BROKEN IMAGE] T1.png missing in {relative_path}: {img_path}")
            
        except Exception as e:
            errors.append(f"Error processing {relative_path}: {str(e)}")

    if errors:
        print("\nErrors Found:")
        for error in errors:
            print(error)
    else:
        print("\nNo broken links found!")

if __name__ == "__main__":
    validate_links()
