#!/usr/bin/env python3
"""
Titan Gym Biometric Synchronization & Logging Middleware Agent
This script runs locally on the gym's computer connected to both the fingerprint biometric machine
(ZKTeco protocols) and the PHP Web Server over the Local Area Network (LAN).

It handles:
1. Fetching authorized/unauthorized users from the PHP portal and synchronizing active privileges.
2. Backing up fingerprint templates locally before deactivating users, and restoring them on renewal.
3. Reading real-time/historical logs from the fingerprint machine and pushing them to the portal.
4. An end-to-end SIMULATION MODE for local testing without physical hardware.
"""

import os
import sys
import json
import base64
import time
import requests
import argparse
from datetime import datetime

# ANSI colors for styling command-line logs
COLOR_RESET = "\033[0m"
COLOR_BOLD = "\033[1m"
COLOR_RED = "\033[31m"
COLOR_GREEN = "\033[32m"
COLOR_YELLOW = "\033[33m"
COLOR_BLUE = "\033[34m"
COLOR_MAGENTA = "\033[35m"
COLOR_CYAN = "\033[36m"

# Load PyZK dynamically to allow simulation mode without installing libraries
PYZK_AVAILABLE = False
try:
    from zk import ZK, const
    from zk.user import User
    from zk.finger import Finger
    PYZK_AVAILABLE = True
except ImportError:
    # We will define placeholder classes for compilation checks if pyzk is not installed
    class User:
        def __init__(self, uid, user_id, name, privilege=0, password='', group_id=1, card=0):
            self.uid = uid
            self.user_id = user_id
            self.name = name
            self.privilege = privilege
            self.password = password
            self.group_id = group_id
            self.card = card
    class Finger:
        def __init__(self, uid, fid, valid, template):
            self.uid = uid
            self.fid = fid
            self.valid = valid
            self.template = template

# Mock Attendance class for Simulator
class MockAttendance:
    def __init__(self, user_id, timestamp, status=0, punch=1):
        self.user_id = str(user_id)
        self.timestamp = timestamp if isinstance(timestamp, datetime) else datetime.strptime(timestamp, '%Y-%m-%d %H:%M:%S')
        self.status = status
        self.punch = punch

# Simulated Device Database class
class SimulatedDevice:
    def __init__(self, filepath="simulated_device.json"):
        self.filepath = filepath
        self.data = {
            "users": [],
            "templates": [],
            "logs": []
        }
        self.load()

    def load(self):
        if os.path.exists(self.filepath):
            try:
                with open(self.filepath, 'r') as f:
                    self.data = json.load(f)
            except Exception as e:
                print(f"{COLOR_YELLOW}Warning: Simulated device database load failed, starting fresh. {e}{COLOR_RESET}")
        else:
            self.save()

    def save(self):
        try:
            with open(self.filepath, 'w') as f:
                json.dump(self.data, f, indent=4)
        except Exception as e:
            print(f"{COLOR_RED}Error saving simulated device database: {e}{COLOR_RESET}")

# Mock ZK Connection object for Simulator
class MockZKConn:
    def __init__(self, dev):
        self.dev = dev

    def disable_device(self):
        pass

    def enable_device(self):
        pass

    def disconnect(self):
        pass

    def get_users(self):
        self.dev.load()
        users = []
        for u in self.dev.data["users"]:
            users.append(User(
                uid=u["uid"],
                user_id=str(u["uid"]),
                name=u["name"],
                privilege=u.get("privilege", 0),
                password=u.get("password", ""),
                group_id=u.get("group_id", 1),
                card=u.get("card", 0)
            ))
        return users

    def get_templates(self):
        self.dev.load()
        fingers = []
        for t in self.dev.data["templates"]:
            fingers.append(Finger(
                uid=t["uid"],
                fid=t["fid"],
                valid=t["valid"],
                template=base64.b64decode(t["template_b64"])
            ))
        return fingers

    def save_user_template(self, user, fingers):
        self.dev.load()
        
        # Upsert User
        user_exists = False
        for idx, u in enumerate(self.dev.data["users"]):
            if u["uid"] == user.uid:
                self.dev.data["users"][idx] = {
                    "uid": user.uid,
                    "name": user.name,
                    "privilege": user.privilege
                }
                user_exists = True
                break
        if not user_exists:
            self.dev.data["users"].append({
                "uid": user.uid,
                "name": user.name,
                "privilege": user.privilege
            })

        # Upsert Templates
        for finger in fingers:
            temp_exists = False
            b64_temp = base64.b64encode(finger.template).decode('utf-8')
            for idx, t in enumerate(self.dev.data["templates"]):
                if t["uid"] == finger.uid and t["fid"] == finger.fid:
                    self.dev.data["templates"][idx] = {
                        "uid": finger.uid,
                        "fid": finger.fid,
                        "valid": finger.valid,
                        "template_b64": b64_temp
                    }
                    temp_exists = True
                    break
            if not temp_exists:
                self.dev.data["templates"].append({
                    "uid": finger.uid,
                    "fid": finger.fid,
                    "valid": finger.valid,
                    "template_b64": b64_temp
                })
        self.dev.save()
        return True

    def delete_user(self, uid=0, user_id=''):
        self.dev.load()
        target_uid = uid if uid > 0 else (int(user_id) if user_id.isdigit() else 0)
        if target_uid <= 0:
            return False
        
        initial_user_count = len(self.dev.data["users"])
        self.dev.data["users"] = [u for u in self.dev.data["users"] if u["uid"] != target_uid]
        self.dev.data["templates"] = [t for t in self.dev.data["templates"] if t["uid"] != target_uid]
        
        self.dev.save()
        return len(self.dev.data["users"]) < initial_user_count

    def get_attendance(self):
        self.dev.load()
        attendances = []
        for log in self.dev.data["logs"]:
            attendances.append(MockAttendance(
                user_id=log["user_id"],
                timestamp=log["timestamp"],
                status=log.get("status", 0),
                punch=log.get("punch", 1)
            ))
        return attendances

    def clear_attendance(self):
        self.dev.load()
        self.dev.data["logs"] = []
        self.dev.save()
        return True


class MockZK:
    def __init__(self, ip, port=4370, timeout=5, **kwargs):
        self.ip = ip
        self.port = port
        self.dev = SimulatedDevice()

    def connect(self):
        print(f"{COLOR_CYAN}[SIMULATOR] Connected to simulated biometric device at {self.ip}:{self.port}{COLOR_RESET}")
        return MockZKConn(self.dev)


# Core Agent Middleware Class
class BiometricSyncAgent:
    def __init__(self, config_path="biometric_config.json"):
        self.config_path = config_path
        self.config = {
            "device_ip": "192.168.1.201",
            "device_port": 4370,
            "api_sync_url": "http://localhost/api/biometric_sync.php",
            "api_log_url": "http://localhost/api/log_attendance.php",
            "sync_interval_seconds": 10,
            "simulation_mode": True,
            "templates_cache_dir": "templates_cache",
            "checkpoint_file": "sync_checkpoint.txt"
        }
        self.load_config()
        
        # Ensure directories exist
        if not os.path.exists(self.config["templates_cache_dir"]):
            os.makedirs(self.config["templates_cache_dir"])

    def load_config(self):
        if os.path.exists(self.config_path):
            try:
                with open(self.config_path, 'r') as f:
                    loaded = json.load(f)
                    self.config.update(loaded)
                print(f"{COLOR_GREEN}Loaded configurations from {self.config_path}{COLOR_RESET}")
            except Exception as e:
                print(f"{COLOR_YELLOW}Warning: Failed to load config file {self.config_path}. Using defaults. {e}{COLOR_RESET}")
        else:
            self.save_config()

    def save_config(self):
        try:
            with open(self.config_path, 'w') as f:
                json.dump(self.config, f, indent=4)
            print(f"{COLOR_GREEN}Created configuration template at {self.config_path}{COLOR_RESET}")
        except Exception as e:
            print(f"{COLOR_RED}Error saving config file: {e}{COLOR_RESET}")

    def log_timestamp(self):
        return f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}]"

    def get_local_templates(self, biometric_id):
        filepath = os.path.join(self.config["templates_cache_dir"], f"user_{biometric_id}.json")
        if os.path.exists(filepath):
            try:
                with open(filepath, 'r') as f:
                    return json.load(f)
            except Exception as e:
                print(f"{COLOR_RED}{self.log_timestamp()} Error reading templates cache for user {biometric_id}: {e}{COLOR_RESET}")
        return None

    def save_local_templates(self, biometric_id, name, templates):
        filepath = os.path.join(self.config["templates_cache_dir"], f"user_{biometric_id}.json")
        try:
            data = {
                "biometric_id": biometric_id,
                "name": name,
                "saved_at": datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
                "fingers": []
            }
            for t in templates:
                data["fingers"].append({
                    "fid": t.fid,
                    "valid": t.valid,
                    "template_b64": base64.b64encode(t.template).decode('utf-8')
                })
            with open(filepath, 'w') as f:
                json.dump(data, f, indent=4)
            return True
        except Exception as e:
            print(f"{COLOR_RED}{self.log_timestamp()} Error saving local templates backup for user {biometric_id}: {e}{COLOR_RESET}")
        return False

    def sync_users_state(self, conn):
        print(f"{COLOR_BLUE}{self.log_timestamp()} Synchronizing user privileges...{COLOR_RESET}")
        
        # 1. Fetch current status from Web Portal API
        try:
            res = requests.get(self.config["api_sync_url"], timeout=10)
            if res.status_code != 200:
                print(f"{COLOR_RED}{self.log_timestamp()} Portal API sync returned error status {res.status_code}{COLOR_RESET}")
                return
            portal_users = res.json()
        except Exception as e:
            print(f"{COLOR_RED}{self.log_timestamp()} Failed to connect to Portal API ({self.config['api_sync_url']}): {e}{COLOR_RESET}")
            return

        # 2. Fetch enrolled users and templates from physical device
        try:
            device_users = conn.get_users()
            device_templates = conn.get_templates()
        except Exception as e:
            print(f"{COLOR_RED}{self.log_timestamp()} Failed to fetch users/templates from device: {e}{COLOR_RESET}")
            return

        # Map templates to uids
        templates_map = {}
        for temp in device_templates:
            if temp.uid not in templates_map:
                templates_map[temp.uid] = []
            templates_map[temp.uid].append(temp)

        # Index device users by biometric ID
        device_users_map = {u.uid: u for u in device_users}

        # Iterate over gym portal users and sync
        for pu in portal_users:
            bio_id = pu["biometric_id"]
            username = pu["username"]
            status = pu["status"]  # 'active' or 'inactive'

            # Ensure we are dealing with numeric ID
            try:
                device_uid = int(bio_id)
            except ValueError:
                print(f"{COLOR_YELLOW}{self.log_timestamp()} Invalid non-numeric Biometric ID: {bio_id} for user {username}{COLOR_RESET}")
                continue

            on_device = device_uid in device_users_map

            if status == 'active':
                if not on_device:
                    # User needs to be added/restored on the device
                    print(f"{COLOR_YELLOW}{self.log_timestamp()} Member '{username}' (Bio ID: {bio_id}) is ACTIVE but missing on device. Attempting restoration...{COLOR_RESET}")
                    
                    # Look up local backup cache of templates
                    cached = self.get_local_templates(device_uid)
                    if cached and cached.get("fingers"):
                        # Build user object
                        new_user = User(uid=device_uid, user_id=str(device_uid), name=username, privilege=0)
                        
                        # Build finger template objects
                        restored_fingers = []
                        for f_data in cached["fingers"]:
                            restored_fingers.append(Finger(
                                uid=device_uid,
                                fid=f_data["fid"],
                                valid=f_data["valid"],
                                template=base64.b64decode(f_data["template_b64"])
                            ))
                        
                        # Save to device
                        try:
                            conn.save_user_template(new_user, restored_fingers)
                            print(f"{COLOR_GREEN}{self.log_timestamp()} Successfully restored '{username}' (ID: {bio_id}) with {len(restored_fingers)} fingerprint templates from local cache.{COLOR_RESET}")
                        except Exception as e:
                            print(f"{COLOR_RED}{self.log_timestamp()} Failed to restore templates to device for '{username}': {e}{COLOR_RESET}")
                    else:
                        # No local backup. We will enroll the shell user profile, templates must be scanned directly
                        print(f"{COLOR_YELLOW}{self.log_timestamp()} No local templates backup for '{username}' (ID: {bio_id}). Creating user shell profile on device. Fingerprint enrollment must be done manually on the device under ID {bio_id}.{COLOR_RESET}")
                        new_user = User(uid=device_uid, user_id=str(device_uid), name=username, privilege=0)
                        try:
                            conn.save_user_template(new_user, [])
                        except Exception as e:
                            print(f"{COLOR_RED}{self.log_timestamp()} Failed to create user shell on device: {e}{COLOR_RESET}")
                else:
                    # User is on device and active. Let's backup their templates locally in case they expire later
                    device_user_temps = templates_map.get(device_uid, [])
                    if len(device_user_temps) > 0:
                        # Check if local templates match / are stored
                        cached = self.get_local_templates(device_uid)
                        if not cached or len(cached.get("fingers", [])) != len(device_user_temps):
                            self.save_local_templates(device_uid, username, device_user_temps)
                            print(f"{COLOR_CYAN}{self.log_timestamp()} Backed up / Updated templates cache locally for '{username}' (ID: {bio_id}, fingers: {len(device_user_temps)}).{COLOR_RESET}")

            elif status == 'inactive':
                if on_device:
                    # User is inactive (blocked / expired) but still exists on device. Remove them to revoke access!
                    print(f"{COLOR_YELLOW}{self.log_timestamp()} Member '{username}' (Bio ID: {bio_id}) is INACTIVE/EXPIRED. Revoking door access...{COLOR_RESET}")
                    
                    # 1. Back up templates from the device before deleting
                    device_user_temps = templates_map.get(device_uid, [])
                    if len(device_user_temps) > 0:
                        self.save_local_templates(device_uid, username, device_user_temps)
                        print(f"{COLOR_CYAN}{self.log_timestamp()} Backed up templates locally for '{username}' before deletion.{COLOR_RESET}")
                    else:
                        print(f"{COLOR_YELLOW}{self.log_timestamp()} WARNING: No templates found on device to back up for '{username}'.{COLOR_RESET}")

                    # 2. Delete user from device
                    try:
                        success = conn.delete_user(uid=device_uid)
                        if success:
                            print(f"{COLOR_GREEN}{self.log_timestamp()} Revoked access: Deleted '{username}' (ID: {bio_id}) from the biometric device.{COLOR_RESET}")
                        else:
                            print(f"{COLOR_RED}{self.log_timestamp()} Device refused user deletion for '{username}' (ID: {bio_id}).{COLOR_RESET}")
                    except Exception as e:
                        print(f"{COLOR_RED}{self.log_timestamp()} Exception deleting user '{username}' from device: {e}{COLOR_RESET}")

    def sync_attendance_logs(self, conn):
        print(f"{COLOR_BLUE}{self.log_timestamp()} Synchronizing attendance logs...{COLOR_RESET}")

        # 1. Load checkpoint (last synced timestamp)
        last_checkpoint = datetime.min
        if os.path.exists(self.config["checkpoint_file"]):
            try:
                with open(self.config["checkpoint_file"], 'r') as f:
                    dt_str = f.read().strip()
                    if dt_str:
                        last_checkpoint = datetime.strptime(dt_str, '%Y-%m-%d %H:%M:%S')
                print(f"{COLOR_CYAN}{self.log_timestamp()} Last synced log checkpoint: {last_checkpoint}{COLOR_RESET}")
            except Exception as e:
                print(f"{COLOR_YELLOW}{self.log_timestamp()} Warning reading checkpoint file: {e}. Syncing all records.{COLOR_RESET}")

        # 2. Get attendance logs from biometric machine
        try:
            logs = conn.get_attendance()
        except Exception as e:
            print(f"{COLOR_RED}{self.log_timestamp()} Failed to retrieve logs from device: {e}{COLOR_RESET}")
            return

        print(f"{COLOR_CYAN}{self.log_timestamp()} Retrieved {len(logs)} logs from device.{COLOR_RESET}")

        # Sort logs chronologically
        logs.sort(key=lambda x: x.timestamp)

        new_max_checkpoint = last_checkpoint
        sync_count = 0

        for log in logs:
            log_time = log.timestamp
            
            # Skip logs at or before last checkpoint
            if log_time <= last_checkpoint:
                continue

            bio_id = log.user_id
            timestamp_str = log_time.strftime('%Y-%m-%d %H:%M:%S')

            # POST to local PHP API
            payload = {
                "biometric_id": bio_id,
                "timestamp": timestamp_str
            }

            print(f"{COLOR_YELLOW}{self.log_timestamp()} Syncing new punch: User ID {bio_id} at {timestamp_str}...{COLOR_RESET}")
            try:
                headers = {'Content-Type': 'application/json'}
                res = requests.post(self.config["api_log_url"], json=payload, headers=headers, timeout=10)
                if res.status_code == 200:
                    resp_json = res.json()
                    action = resp_json.get("action", "punch")
                    member_name = resp_json.get("name", f"ID {bio_id}")
                    print(f"{COLOR_GREEN}{self.log_timestamp()} Successful sync! Member '{member_name}' registered as {action.upper()}{COLOR_RESET}")
                    
                    # Keep track of latest successfully synced log timestamp
                    if log_time > new_max_checkpoint:
                        new_max_checkpoint = log_time
                    sync_count += 1
                else:
                    try:
                        err_msg = res.json().get("message", "Unknown error")
                    except Exception:
                        err_msg = res.text
                    print(f"{COLOR_RED}{self.log_timestamp()} Portal API rejected punch: Code {res.status_code} - {err_msg}{COLOR_RESET}")
                    
                    # If it's a 404 (user mapping not found), we should still advance checkpoint to avoid hanging on unknown guest punches
                    if res.status_code == 404:
                        print(f"{COLOR_YELLOW}{self.log_timestamp()} Advancing checkpoint past unmapped Biometric ID: {bio_id} to prevent loop blockage.{COLOR_RESET}")
                        if log_time > new_max_checkpoint:
                            new_max_checkpoint = log_time
            except Exception as e:
                print(f"{COLOR_RED}{self.log_timestamp()} Failed to POST punch to PHP Server: {e}. Pausing log sync.{COLOR_RESET}")
                break

        # Save new checkpoint
        if new_max_checkpoint > last_checkpoint:
            try:
                with open(self.config["checkpoint_file"], 'w') as f:
                    f.write(new_max_checkpoint.strftime('%Y-%m-%d %H:%M:%S'))
                print(f"{COLOR_GREEN}{self.log_timestamp()} Checkpoint updated to {new_max_checkpoint.strftime('%Y-%m-%d %H:%M:%S')} ({sync_count} new logs synced).{COLOR_RESET}")
            except Exception as e:
                print(f"{COLOR_RED}{self.log_timestamp()} Error updating checkpoint file: {e}{COLOR_RESET}")

    def run(self):
        print(f"\n{COLOR_BOLD}{COLOR_MAGENTA}========================================================================{COLOR_RESET}")
        print(f"{COLOR_BOLD}{COLOR_MAGENTA}      TITAN GYM BIOMETRIC MIDDLEWARE RUNNER INITIALIZED                 {COLOR_RESET}")
        print(f"{COLOR_BOLD}{COLOR_MAGENTA}========================================================================{COLOR_RESET}")
        print(f"{COLOR_CYAN}Device IP: {self.config['device_ip']}:{self.config['device_port']}")
        print(f"Sync API:  {self.config['api_sync_url']}")
        print(f"Log API:   {self.config['api_log_url']}")
        print(f"Interval:  {self.config['sync_interval_seconds']} seconds")
        print(f"Simulation Mode: {'ENABLED' if self.config['simulation_mode'] else 'DISABLED'}")
        print(f"Local Cache:     {self.config['templates_cache_dir']}/")
        print(f"{COLOR_MAGENTA}========================================================================{COLOR_RESET}\n")

        # Connection class selection
        if self.config["simulation_mode"]:
            zk_class = MockZK
        else:
            if not PYZK_AVAILABLE:
                print(f"{COLOR_RED}Fatal Error: 'pyzk' package is missing but simulation_mode is False.{COLOR_RESET}")
                print("Please install the requirements first: pip3 install pyzk requests")
                sys.exit(1)
            zk_class = ZK

        while True:
            zk = zk_class(self.config["device_ip"], port=self.config["device_port"], timeout=5)
            conn = None
            try:
                print(f"{COLOR_CYAN}{self.log_timestamp()} Connecting to biometric device...{COLOR_RESET}")
                conn = zk.connect()
                
                # Lock device commands during batch sync
                conn.disable_device()
                
                # Perform synchronization tasks
                self.sync_users_state(conn)
                self.sync_attendance_logs(conn)
                
            except Exception as e:
                print(f"{COLOR_RED}{self.log_timestamp()} Connection or sync error: {e}{COLOR_RESET}")
            finally:
                if conn:
                    try:
                        conn.enable_device()
                        conn.disconnect()
                        print(f"{COLOR_CYAN}{self.log_timestamp()} Disconnected cleanly from device. Sleeping...{COLOR_RESET}")
                    except Exception:
                        pass

            # Write heartbeat timestamp to sync file
            try:
                base_dir = os.path.dirname(os.path.abspath(__file__))
                heartbeat_path = os.path.join(base_dir, "include", "last_sync_heartbeat.txt")
                with open(heartbeat_path, "w") as hf:
                    hf.write(str(int(time.time())))
            except Exception as he:
                print(f"{COLOR_RED}Failed to write heartbeat: {he}{COLOR_RESET}")

            # Sleep until next check
            time.sleep(self.config["sync_interval_seconds"])


# Helper function to trigger simulated user events
def trigger_simulator_punch(biometric_id):
    sim = SimulatedDevice()
    # Check if user exists in simulation
    user_found = False
    for u in sim.data["users"]:
        if u["uid"] == biometric_id:
            user_found = True
            break
    if not user_found:
        print(f"{COLOR_RED}Error: Biometric ID {biometric_id} is not registered in the simulated device.{COLOR_RESET}")
        print("Please map the user first in the Admin Biometric Access Control page of the gym portal.")
        return
        
    now_str = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    sim.data["logs"].append({
        "user_id": str(biometric_id),
        "timestamp": now_str,
        "status": 0,
        "punch": 1
    })
    sim.save()
    print(f"{COLOR_GREEN}[SIMULATOR] Scanned finger for Biometric ID {biometric_id} at {now_str} (Registered on simulated device).{COLOR_RESET}")


if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="Titan Gym Biometric Synchronization Runner")
    parser.add_argument("--simulate-punch", type=int, help="Simulate a finger scan on the mock device for a specific Biometric ID")
    parser.add_argument("--simulate-clear", action="store_true", help="Clear logs stored on the simulated device database")
    args = parser.parse_args()

    if args.simulate_punch:
        trigger_simulator_punch(args.simulate_punch)
        sys.exit(0)

    if args.simulate_clear:
        dev = SimulatedDevice()
        dev.data["logs"] = []
        dev.save()
        print(f"{COLOR_GREEN}[SIMULATOR] Cleared logs database.{COLOR_RESET}")
        sys.exit(0)

    # Start main daemon agent loop
    agent = BiometricSyncAgent()
    try:
        agent.run()
    except KeyboardInterrupt:
        print(f"\n{COLOR_YELLOW}Agent shutdown request received. Exiting runner.{COLOR_RESET}")
        sys.exit(0)
