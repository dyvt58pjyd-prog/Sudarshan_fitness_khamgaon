import os

directory = '/Users/anuragbawaskar/Sudarshan Fitness Orignal Data /Files'

replacements = {
    '#030712': '#0f0a05',
    '#010309': '#080502',
    '#ff003c': '#ff7b00',
    '#d90429': '#e65c00',
    'rgba(255, 0, 60,': 'rgba(255, 123, 0,',
    'rgba(255,0,60,': 'rgba(255,123,0,',
    '#7000ff': '#1e90ff',
    'rgba(112, 0, 255,': 'rgba(30, 144, 255,',
    'rgba(112,0,255,': 'rgba(30,144,255,',
    'Solo Leveling': 'Hidden Leaf',
    'System Gate': 'Hidden Leaf Portal',
    'Monarch': 'Hokage'
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
