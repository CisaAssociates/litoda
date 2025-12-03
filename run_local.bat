@echo off
echo ==========================================
echo  LITODA Face Recognition System - Local Setup
echo ==========================================

REM Check if Python is installed
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo Error: Python is not installed or not in PATH.
    echo Please install Python 3.10 or higher from python.org
    pause
    exit /b
)

REM Create Virtual Environment if it doesn't exist
if not exist "venv" (
    echo Creating virtual environment...
    python -m venv venv
)

REM Activate Virtual Environment
echo Activating virtual environment...
call venv\Scripts\activate

REM Install dependencies
echo Installing dependencies...
pip install -r requirements.txt

echo.
echo ==========================================
echo  Starting Python Server...
echo  Make sure XAMPP MySQL is running!
echo ==========================================
echo.

python face_recognition_system.py

pause
