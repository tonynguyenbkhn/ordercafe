import re
from pathlib import Path
import sys

root = Path(__file__).resolve().parent
helper = root / 'inc' / 'woocommerces' / 'helper.php'
text = helper.read_text(encoding='utf-8')

patterns = {
    'tinh_thanh_pho': r'(function get_tinh_thanh_pho\s*\(\)\s*\{.*?\n\}\n\n)',
    'quan_huyen': r'(function get_quan_huyen\s*\(\)\s*\{.*?\n\}\n\n)',
    'xa_phuong_thi_tran': r'(function get_xa_phuong_thi_tran\s*\(\)\s*\{.*?\n\}\n)'
}

data_dir = root / 'inc' / 'woocommerces' / 'data'
data_dir.mkdir(parents=True, exist_ok=True)
new_text = text

for key, pattern in patterns.items():
    m = re.search(pattern, text, flags=re.S)
    if not m:
        print(f'ERROR: Pattern not found: {key}')
        sys.exit(1)
    block = m.group(1)
    inner = re.sub(r'^function \w+\s*\(\)\s*\{\s*\n', '', block)
    inner = re.sub(r'\n\}\s*\n?$', '', inner)
    out = data_dir / f'{key}.php'
    out.write_text('<?php\nreturn ' + inner.strip() + '\n;', encoding='utf-8')
    print('wrote', out)
    wrapper = f"function get_{key}() {{\n    return twmp_load_location_data('{key}');\n}}\n\n"
    new_text = re.sub(pattern, wrapper, new_text, count=1, flags=re.S)

insert = '''function twmp_load_location_data($key)\n{\n    static $cache = array();\n\n    if (isset($cache[$key])) {\n        return $cache[$key];\n    }\n\n    $file = get_theme_file_path("inc/woocommerces/data/{$key}.php");\n    if (! file_exists($file)) {\n        return array();\n    }\n\n    $data = require $file;\n    if (! is_array($data)) {\n        return array();\n    }\n\n    $cache[$key] = $data;\n    return $data;\n}\n\n'''

idx = new_text.find('function get_tinh_thanh_pho()')
if idx == -1:
    print('ERROR: Marker not found for inserting helper')
    sys.exit(1)

new_text = new_text[:idx] + insert + new_text[idx:]
helper.write_text(new_text, encoding='utf-8')
print('helper updated')
