import os

directory = '/Users/anuragbawaskar/Sudarshan Fitness Orignal Data /Files'

replacements = {
    '#0f0a05': '#2c1b18',
    '#080502': '#1f100a',
    '#ff7b00': '#ff6b00',
    '#e65c00': '#e65000',
    'rgba(255, 123, 0,': 'rgba(255, 107, 0,',
    'rgba(255,123,0,': 'rgba(255,107,0,',
    '#1e90ff': '#ffd700',
    'rgba(30, 144, 255,': 'rgba(255, 215, 0,',
    'rgba(30,144,255,': 'rgba(255,215,0,',
    'Hidden Leaf': 'Shree Ganesha',
    'Hokage': 'Vighnaharta',
    'hanuman_gym.jpg': 'ganesha_gym.jpg',
    'AWAKEN YOUR INNER STRENGTH': 'OM GANAPATAYE NAMAHA',
    'Sharingan': 'Divine'
}

count = 0
for root, dirs, files in os.walk(directory):
    for file in files:
        if file.endswith(('.php', '.css', '.html', '.js')):
            filepath = os.path.join(root, file)
            try:
                with open(filepath, 'r', encoding='utf-8') as f:
                    content = f.read()
                
                new_content = content
                for old, new in replacements.items():
                    new_content = new_content.replace(old, new)
                
                if new_content != content:
                    with open(filepath, 'w', encoding='utf-8') as f:
                        f.write(new_content)
                    count += 1
            except Exception as e:
                print(f"Error processing {filepath}: {e}")

print(f"Updated {count} files.")
