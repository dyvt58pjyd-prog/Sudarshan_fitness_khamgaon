import os
import sys
import threading
import time
import webview

# Import the existing Biometric Sync Agent
try:
    from biometric_sync_agent import BiometricSyncAgent
except ImportError:
    print("Error: Could not find biometric_sync_agent.py. Ensure this script is in the same directory.")
    sys.exit(1)

def run_biometric_agent():
    """Run the biometric sync agent in an infinite loop inside a daemon thread."""
    try:
        print("[Desktop] Starting Biometric Sync Background Agent...")
        agent = BiometricSyncAgent()
        agent.run()
    except Exception as e:
        print(f"[Desktop] Biometric Agent crashed: {e}")

if __name__ == '__main__':
    # Start the background sync agent as a daemon thread.
    # Daemon threads automatically exit when the main program finishes.
    agent_thread = threading.Thread(target=run_biometric_agent, daemon=True)
    agent_thread.start()

    # Define the URL of the hosted Gym Software
    GYM_URL = "https://sudarshanfitness.de"
    
    print(f"[Desktop] Launching Web Interface for {GYM_URL}...")
    
    # Create the PyWebView Window
    window = webview.create_window(
        title='SUDARSHAN FITNESS KHAMGAON',
        url=GYM_URL,
        width=1280,
        height=800,
        resizable=True,
        fullscreen=False,
        text_select=True,
        confirm_close=True # Prompts the user before closing
    )
    
    # Start the webview application.
    # This call blocks until the window is closed.
    webview.start(private_mode=False)
    
    print("[Desktop] Window closed. Shutting down application...")
    # The application exits here, automatically terminating the daemon agent_thread.
