#!/bin/bash

# Create virtual environment
python3 -m venv venv
source venv/bin/activate

# Install requirements
pip install -r requirements.txt

echo "Setup complete. Run 'source apps/canary-atomy-tui/venv/bin/activate && python3 apps/canary-atomy-tui/src/app.py' to start the TUI."
