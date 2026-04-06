import os
import re

pages = ['about.php', 'contact.php', 'shipping.php', 'returns.php', 'terms.php', 'privacy.php', 'cookie.php']

for page in pages:
    if not os.path.exists(page): continue
    with open(page, 'r', encoding='utf-8') as f:
        content = f.read()

    match = re.search(r'(<!-- Hero Section -->.*?)(\s*</div>\s*<?= modernFooter\(\) \?>)', content, re.DOTALL)
    if match:
        inner_content = match.group(1)
        
        # write en
        with open(f'src/pages/en/{page}', 'w', encoding='utf-8') as f:
            f.write(inner_content)
            
        # write ku
        ku_content = inner_content
        ku_content = ku_content.replace('About Us', '???????? ????')
        ku_content = ku_content.replace('Contact Us', '???????????? ?? ??????')
        ku_content = ku_content.replace('Shipping Information', '??????? ???????')
        ku_content = ku_content.replace('Returns & Refunds Policy', '??????? ?????????? ? ??????????')
        ku_content = ku_content.replace('Terms of Service', '????????? ???????????')
        ku_content = ku_content.replace('Privacy Policy', '??????? ???????????')
        ku_content = ku_content.replace('Cookie Policy', '??????? ????')

        with open(f'src/pages/ku/{page}', 'w', encoding='utf-8') as f:
            f.write(ku_content)

        new_content = content[:match.start()] + f"""        <?php 
        // Dynamically include the content payload based on active language
        $langFile = __DIR__ . "/src/pages/{{$lang}}/{page}";
        if (file_exists($langFile)) {{
            include $langFile;
        }} else {{
            // Fallback to English if translation is missing
            include __DIR__ . "/src/pages/en/{page}";
        }}
        ?>""" + match.group(2)
        
        with open(page, 'w', encoding='utf-8') as f:
            f.write(new_content)

#'@
python fix_pages.py
Get-Content cookie.php
Set-Content -Path fix_pages.py -Value @"
import os
import re

pages = ['about.php', 'contact.php', 'shipping.php', 'returns.php', 'terms.php', 'privacy.php', 'cookie.php']

for page in pages:
    if not os.path.exists(page): continue
    with open(page, 'r', encoding='utf-8') as f:
        content = f.read()

    match = re.search(r'(<!-- Hero Section -->.*?)(\s*</div>\s*<\?= modernFooter\(\) \?>)', content, re.DOTALL)
    if match:
        inner_content = match.group(1)
        
        with open(f'src/pages/en/{{page}}', 'w', encoding='utf-8') as f:
            f.write(inner_content)
            
        ku_content = inner_content
        ku_content = ku_content.replace('About Us', '???????? ????')
        ku_content = ku_content.replace('Contact Us', '???????????? ?? ??????')
        ku_content = ku_content.replace('Shipping Information', '??????? ???????')
        ku_content = ku_content.replace('Returns & Refunds Policy', '??????? ?????????? ? ??????????')
        ku_content = ku_content.replace('Terms of Service', '????????? ???????????')
        ku_content = ku_content.replace('Privacy Policy', '??????? ???????????')
        ku_content = ku_content.replace('Cookie Policy', '??????? ????')

        with open(f'src/pages/ku/{{page}}', 'w', encoding='utf-8') as f:
            f.write(ku_content)

        new_content = content[:match.start()] + f'''        <?php 
        // Dynamically include the content payload based on active language
        \$langFile = __DIR__ . "/src/pages/{\$lang}/{{page}}";
        if (file_exists(\$langFile)) {{
            include \$langFile;
        }} else {{
            // Fallback to English if translation is missing
            include __DIR__ . "/src/pages/en/{{page}}";
        }}
        ?>''' + match.group(2)
        
        with open(page, 'w', encoding='utf-8') as f:
            f.write(new_content)
"@
python fix_pages.py
Ctrl+C
Get-Content cookie.php -Tail 20

