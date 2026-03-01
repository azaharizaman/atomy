#!/bin/bash
# Get the directory where this script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "Setting up Atomy TUI in $SCRIPT_DIR..."

# Create virtual environment if it doesn't exist
if [ ! -d "venv" ]; then
    python3 -m venv venv
fi

source venv/bin/activate

# Install requirements
pip install -r requirements.txt

echo ""
echo "Setup complete."
echo "You can now run the TUI using: ./run.sh (from this directory) or ./apps/canary-atomy-tui/run.sh (from root)"
