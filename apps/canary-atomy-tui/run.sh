#!/bin/bash
source apps/canary-atomy-tui/venv/bin/activate
export PYTHONPATH=$PYTHONPATH:$(pwd)/apps/canary-atomy-tui
python3 apps/canary-atomy-tui/src/app.py
