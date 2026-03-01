#!/bin/bash
# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# Navigate to the monorepo root
MONOREPO_ROOT="$( cd "$SCRIPT_DIR/../.." && pwd )"

# Activate virtual environment
source "$SCRIPT_DIR/venv/bin/activate"

# Add the 'src' directory to PYTHONPATH so 'api_client' can be imported directly
export PYTHONPATH="$SCRIPT_DIR/src:$PYTHONPATH"

# Run the app
python3 "$SCRIPT_DIR/src/app.py"
