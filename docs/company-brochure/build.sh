#!/usr/bin/env bash
# Builds the company brochure PDFs from ar.html and en.html (Git Bash on Windows).
# Needs Chrome or Edge, and internet access for the Google Fonts.
#   bash docs/company-brochure/build.sh
set -euo pipefail

cd "$(dirname "$0")"
here="$(pwd -W 2>/dev/null || pwd)"

browser=""
for candidate in \
    "/c/Program Files/Google/Chrome/Application/chrome.exe" \
    "/c/Program Files (x86)/Google/Chrome/Application/chrome.exe" \
    "/c/Program Files (x86)/Microsoft/Edge/Application/msedge.exe" \
    "/c/Program Files/Microsoft/Edge/Application/msedge.exe"; do
    if [ -f "$candidate" ]; then
        browser="$candidate"
        break
    fi
done
[ -n "$browser" ] || { echo "Chrome or Edge not found." >&2; exit 1; }

# Arabic first, then English, in one file.
{
    sed '/<main/,$d' ar.html
    sed -n '/<main/,/<\/main>/p' ar.html
    sed -n '/<main/,/<\/main>/p' en.html
    printf '</body>\n\n</html>\n'
} > both.html

print_pdf() {
    "$browser" --headless=new --disable-gpu --no-pdf-header-footer \
        --virtual-time-budget=20000 \
        --print-to-pdf="$here/$2" "file:///$here/$1" >/dev/null 2>&1
    echo "Built $2"
}

print_pdf ar.html SunXFinder-for-Companies-AR.pdf
print_pdf en.html SunXFinder-for-Companies-EN.pdf
print_pdf both.html SunXFinder-for-Companies-AR-EN.pdf

rm -f both.html
