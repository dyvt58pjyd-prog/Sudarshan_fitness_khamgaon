@echo off
echo =======================================================
echo Titan Gym - Desktop App Compiler (Windows)
echo =======================================================
echo.

echo Checking Python installation...
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] Python is not installed or not in your PATH.
    echo Please install Python from https://www.python.org/downloads/
    echo Make sure to check "Add Python to PATH" during installation.
    pause
    exit /b 1
)

echo.
echo Installing Required Python Libraries...
pip install pywebview requests pyzk pyinstaller Pillow

echo.
echo Generating Application Icon...
python -c "from PIL import Image; img = Image.open('images/logo.jpg'); img.save('logo.ico')"

echo.
echo Compiling sudarshan_desktop.py into an executable...
echo This may take a few minutes. Please wait...
pyinstaller --noconsole --onefile --icon=logo.ico --name="SudarshanFitness" sudarshan_desktop.py

echo.
if exist "dist\SudarshanFitness.exe" (
    echo =======================================================
    echo [SUCCESS] Build Complete!
    echo Your application is located at: dist\SudarshanFitness.exe
    echo You can now copy this .exe file to your Desktop.
    echo =======================================================
) else (
    echo [ERROR] Build failed. Please check the console output above for errors.
)

pause
