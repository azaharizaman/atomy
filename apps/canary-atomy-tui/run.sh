#!/bin/bash
# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Use the python executable from the virtual environment directly
PYTHON_EXEC="$SCRIPT_DIR/venv/bin/activate"
# Wait, activate is a script. The python is in venv/bin/python3
PYTHON_EXEC="$SCRIPT_DIR/venv/bin/python3"

if [ ! -f "$PYTHON_EXEC" ]; then
    echo "Virtual environment not found. Please run ./setup.sh first."
    exit 1
fi

# Add the 'src' directory to PYTHONPATH so 'api_client' can be imported directly
export PYTHONPATH="$SCRIPT_DIR/src:$PYTHONPATH"

# Run the app
"$PYTHON_EXEC" "$SCRIPT_DIR/src/app.py"
