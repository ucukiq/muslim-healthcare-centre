PowerShell deploy helper

Usage:
1. Open PowerShell as Administrator.
2. Run:

   powershell -ExecutionPolicy Bypass -File .\scripts\import_and_deploy.ps1

Notes:
- Script copies project files into `C:\xampp\htdocs\muslim_healthcare_centre`.
- It attempts to find `mysql.exe` in common XAMPP/MySQL paths. If not found, ensure `mysql` is in PATH.
- When prompted, enter MySQL username (default `root`) and password (press Enter for empty).
- After success, open http://localhost/muslim_healthcare_centre/ to test the site.
