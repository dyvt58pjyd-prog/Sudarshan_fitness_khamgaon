import os

filepath = "./Files/dashboard/member/my_routine.php"
with open(filepath, "r", encoding="utf-8") as f:
    content = f.read()

# Replace <script src="../../js/Script.js"></script> with adding marked.js
content = content.replace(
    '<script src="../../js/Script.js"></script>',
    '<script src="../../js/Script.js"></script>\n    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>\n    <style>.routine-text h3 { color: var(--accent-primary); margin-top: 20px; font-family: "JetBrains Mono", monospace; } .routine-text strong { color: #fff; } .routine-text ul { padding-left: 20px; } .routine-text li { margin-bottom: 8px; }</style>'
)

# Render Markdown using ID and script
content = content.replace(
    '<?php echo htmlspecialchars($workout); ?>',
    '<div id="workout_render"></div><script>document.getElementById("workout_render").innerHTML = marked.parse(<?php echo json_encode($workout); ?>);</script>'
)

content = content.replace(
    '<?php echo htmlspecialchars($diet); ?>',
    '<div id="diet_render"></div><script>document.getElementById("diet_render").innerHTML = marked.parse(<?php echo json_encode($diet); ?>);</script>'
)

with open(filepath, "w", encoding="utf-8") as f:
    f.write(content)
print("Patched my_routine.php successfully")
