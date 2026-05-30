$ErrorActionPreference = 'Stop'

$root = (Resolve-Path '.').Path
$docsDir = Join-Path $root 'docs/repo-structure'
if (!(Test-Path -LiteralPath $docsDir)) {
    New-Item -ItemType Directory -Path $docsDir | Out-Null
}

function Write-Utf8NoBom([string]$Path, [string]$Content) {
    $enc = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($Path, $Content, $enc)
}

function Get-RelativePath([string]$BasePath, [string]$TargetPath) {
    $base = (Resolve-Path $BasePath).Path.TrimEnd('\', '/')
    $target = (Resolve-Path $TargetPath).Path

    if ($target.StartsWith($base, [System.StringComparison]::OrdinalIgnoreCase)) {
        return $target.Substring($base.Length).TrimStart('\', '/')
    }

    return $target
}

function Get-TreeLines([string]$Path, [string]$Prefix = '') {
    $children = Get-ChildItem -LiteralPath $Path -Force | Sort-Object @{Expression={ if ($_.PSIsContainer) { 0 } else { 1 } }}, Name
    $lines = @()

    for ($i = 0; $i -lt $children.Count; $i++) {
        $child = $children[$i]
        $isLast = ($i -eq ($children.Count - 1))
        $branch = if ($isLast) { '+-- ' } else { '|-- ' }
        $lines += ($Prefix + $branch + $child.Name)

        if ($child.PSIsContainer) {
            $nextPrefix = if ($isLast) { $Prefix + '    ' } else { $Prefix + '|   ' }
            $lines += Get-TreeLines -Path $child.FullName -Prefix $nextPrefix
        }
    }

    return $lines
}

function Build-MethodMapMd([string]$Title, [string[]]$Paths, [string]$OutputName) {
    $sections = @("# $Title", '')

    foreach ($p in $Paths) {
        $full = Join-Path $root $p
        $raw = Get-Content -LiteralPath $full -Raw
        $rel = $p.Replace('\', '/')

        $classLine = ([regex]::Match($raw, '(?m)^\s*(?:final\s+)?class\s+[^\r\n]+').Value.Trim())
        if ($classLine -eq '') {
            $classLine = ([regex]::Match($raw, '(?m)^\s*interface\s+[^\r\n]+').Value.Trim())
        }

        $methodMatches = [regex]::Matches($raw, '(?m)^\s*(public|protected|private)\s+function\s+([a-zA-Z0-9_]+)\s*\(')

        $sections += "## $rel"
        if ($classLine -ne '') {
            $sections += ''
            $sections += "- Type: $classLine"
        }

        if ($methodMatches.Count -eq 0) {
            $sections += ''
            $sections += '- Methods: (none)'
            $sections += ''
            continue
        }

        $sections += ''
        $sections += '| Visibility | Method |'
        $sections += '|---|---|'
        foreach ($m in $methodMatches) {
            $sections += "| $($m.Groups[1].Value) | $($m.Groups[2].Value) |"
        }
        $sections += ''
    }

    Write-Utf8NoBom -Path (Join-Path $docsDir $OutputName) -Content (($sections + @('')) -join "`n")
}

# 01_REPO_TREE.md
$treeLines = @('PRX') + (Get-TreeLines -Path $root)
$treeMd = @(
    '# 01 - Repo Tree',
    '',
    'Nguon du lieu: quet toan bo thu muc goc hien tai.',
    '',
    '```text',
    ($treeLines -join "`n"),
    '```',
    ''
) -join "`n"
Write-Utf8NoBom -Path (Join-Path $docsDir '01_REPO_TREE.md') -Content $treeMd

# 02_FILE_MANIFEST.md
$textExt = @('.php', '.md', '.txt', '.sql', '.ps1', '.js', '.css', '.json', '.env', '.example', '.puml', '.conf', '.htaccess')
$allFiles = Get-ChildItem -LiteralPath $root -Recurse -File -Force | Sort-Object FullName

$manifestRows = foreach ($f in $allFiles) {
    $rel = (Get-RelativePath -BasePath $root -TargetPath $f.FullName).Replace('\', '/')
    $ext = [System.IO.Path]::GetExtension($f.Name).ToLowerInvariant()
    if ($ext -eq '' -and $f.Name -eq '.htaccess') {
        $ext = '.htaccess'
    }

    $lineCount = ''
    if ($textExt -contains $ext) {
        try {
            $lineCount = (Get-Content -LiteralPath $f.FullName -Encoding UTF8 | Measure-Object -Line).Lines
        } catch {
            $lineCount = ''
        }
    }

    [PSCustomObject]@{
        Path = $rel
        Ext = if ($ext -eq '') { '(none)' } else { $ext }
        Size = $f.Length
        Lines = $lineCount
        Modified = $f.LastWriteTime.ToString('yyyy-MM-dd HH:mm:ss')
    }
}

$manifestHeader = @(
    '# 02 - File Manifest',
    '',
    "Tong so file: $($manifestRows.Count)",
    '',
    '| Path | Ext | Size (bytes) | Lines | Last Modified |',
    '|---|---:|---:|---:|---|'
)
$manifestBody = $manifestRows | ForEach-Object {
    "| $($_.Path) | $($_.Ext) | $($_.Size) | $($_.Lines) | $($_.Modified) |"
}
Write-Utf8NoBom -Path (Join-Path $docsDir '02_FILE_MANIFEST.md') -Content (($manifestHeader + $manifestBody + @('')) -join "`n")

# 03_ARCHITECTURE_OVERVIEW.md
$archMd = @(
    '# 03 - Architecture Overview',
    '',
    '## Stack',
    '',
    '- PHP MVC thuong (khong framework ngoai).',
    '- PDO MySQL.',
    '- Session-based auth + CSRF token.',
    '- AI provider abstraction (OpenAI/Mock).',
    '',
    '## Entry points',
    '',
    '- `index.php` (root) va `public/index.php` deu bootstrap container + router + request dispatch.',
    '- `bootstrap.php` dang ky service trong container, load env, config, repository, services.',
    '',
    '## Layering',
    '',
    '- Core: `Request`, `Response`, `Router`, `Session`, `View`, `Controller` base.',
    '- Controllers: xu ly HTTP + auth/check + render/redirect.',
    '- Services: auth, parser/AI generation, text extraction, scoring.',
    '- Repositories: truy cap DB SQL duy nhat qua `PlatformRepositoryInterface`.',
    '- Views: PHP templates theo module.',
    '- Public assets: CSS + JS behavior cho UX.',
    '',
    '## Role model',
    '',
    '- Roles: `admin`, `user` (table `users.role`).',
    '- Enforce boi `requireAuth([roles])` + owner checks o 1 so controller.',
    '',
    '## Important implementation details',
    '',
    '- Parser import cau hoi va AI suggestion da tach flow ro rang.',
    '- Draft preview luu session (`quiz_generation_draft`).',
    '- Preview submit dung JSON payload de tranh gioi han `max_input_vars`.',
    '- Layout chung co menu role-aware cho admin/user.',
    ''
) -join "`n"
Write-Utf8NoBom -Path (Join-Path $docsDir '03_ARCHITECTURE_OVERVIEW.md') -Content $archMd

# 04_ROUTE_MAP.md
$routeLines = Get-Content -LiteralPath (Join-Path $root 'routes.php')
$routePattern = "\$router->(get|post)\('([^']+)', \[([^:]+)::class, '([^']+)'\]\);"
$routeItems = @()
foreach ($line in $routeLines) {
    if ($line -match $routePattern) {
        $routeItems += [PSCustomObject]@{
            Method = $matches[1].ToUpper()
            Path = $matches[2]
            Controller = $matches[3]
            Action = $matches[4]
        }
    }
}

$roleMap = @{
    'LandingController::index' = 'guest only (redirect if logged in)'
    'AuthController::showLogin' = 'guest only'
    'AuthController::login' = 'guest/form'
    'AuthController::showRegister' = 'guest only'
    'AuthController::register' = 'guest/form'
    'AuthController::logout' = 'auth + csrf'
    'WorkspaceController::index' = 'auth'
    'DocumentController::index' = 'auth (owner scoped for user role)'
    'DocumentController::create' = 'auth'
    'DocumentController::store' = 'auth + csrf + upload validation'
    'DocumentController::show' = 'auth (owner scoped for user role)'
    'QuizController::index' = 'auth'
    'QuizController::create' = 'auth'
    'QuizController::store' = 'auth + csrf + owner check + parser import'
    'QuizController::preview' = 'auth + requires draft in session'
    'QuizController::savePreview' = 'auth + csrf'
    'QuizController::suggestAiPreview' = 'auth + csrf (AI suggestion only)'
    'QuizController::discardPreview' = 'auth + csrf'
    'QuizController::show' = 'auth (admin or quiz creator)'
    'QuizController::take' = 'auth'
    'QuizController::submit' = 'auth + csrf'
    'QuizController::export' = 'admin only'
    'LeaderboardController::index' = 'auth'
    'QuestionController::index' = 'admin only'
    'QuestionController::create' = 'admin only'
    'QuestionController::store' = 'admin only + csrf'
    'QuestionController::edit' = 'admin only'
    'QuestionController::update' = 'admin only + csrf'
    'QuestionController::updateCorrectAnswer' = 'admin only + csrf'
    'QuestionController::delete' = 'admin only + csrf'
    'SubmissionController::index' = 'auth (user sees own submissions)'
    'SubmissionController::show' = 'auth (user sees own submissions)'
    'UserController::index' = 'admin only'
}

$routeHeader = @(
    '# 04 - Route Map And Access',
    '',
    '| Method | Path | Controller::Action | Access Rule |',
    '|---|---|---|---|'
)
$routeBody = $routeItems | ForEach-Object {
    $key = "$($_.Controller)::$($_.Action)"
    $rule = if ($roleMap.ContainsKey($key)) { $roleMap[$key] } else { 'see controller' }
    "| $($_.Method) | $($_.Path) | $key | $rule |"
}
$routeFooter = @(
    '',
    'Ghi chu:',
    '- `/dashboard` dang alias ve `WorkspaceController::index`.',
    '- Kiem soat quyen thuc thi trong `App\Core\Controller::requireAuth()` va cac check theo owner/creator trong tung controller.',
    ''
)
Write-Utf8NoBom -Path (Join-Path $docsDir '04_ROUTE_MAP.md') -Content (($routeHeader + $routeBody + $routeFooter) -join "`n")

# 05/06/07/08 method maps
$controllerFiles = Get-ChildItem -LiteralPath (Join-Path $root 'app/Controllers') -File | Sort-Object Name | ForEach-Object { 'app/Controllers/' + $_.Name }
$serviceFiles = Get-ChildItem -LiteralPath (Join-Path $root 'app/Services') -Recurse -File | Sort-Object FullName | ForEach-Object { (Get-RelativePath -BasePath $root -TargetPath $_.FullName).Replace('\', '/') }
$coreFiles = Get-ChildItem -LiteralPath (Join-Path $root 'app/Core') -File | Sort-Object Name | ForEach-Object { 'app/Core/' + $_.Name }
$repoFiles = Get-ChildItem -LiteralPath (Join-Path $root 'app/Repositories') -File | Sort-Object Name | ForEach-Object { 'app/Repositories/' + $_.Name }
$supportFiles = Get-ChildItem -LiteralPath (Join-Path $root 'app/Support') -File | Sort-Object Name | ForEach-Object { 'app/Support/' + $_.Name }
$exceptionFiles = Get-ChildItem -LiteralPath (Join-Path $root 'app/Exceptions') -File | Sort-Object Name | ForEach-Object { 'app/Exceptions/' + $_.Name }

Build-MethodMapMd -Title '05 - Controller Map' -Paths $controllerFiles -OutputName '05_CONTROLLER_MAP.md'
Build-MethodMapMd -Title '06 - Service Map' -Paths $serviceFiles -OutputName '06_SERVICE_MAP.md'
Build-MethodMapMd -Title '07 - Core Support Exception Map' -Paths ($coreFiles + $supportFiles + $exceptionFiles) -OutputName '07_CORE_SUPPORT_EXCEPTION_MAP.md'
Build-MethodMapMd -Title '08 - Repository Map' -Paths $repoFiles -OutputName '08_REPOSITORY_MAP.md'

# 09_SCHEMA_AND_CONFIG.md
$schema = Get-Content -LiteralPath (Join-Path $root 'database/schema.sql') -Raw
$configApp = Get-Content -LiteralPath (Join-Path $root 'config/app.php') -Raw
$configDb = Get-Content -LiteralPath (Join-Path $root 'config/database.php') -Raw
$schemaMd = @(
    '# 09 - Schema And Config',
    '',
    '## Database Schema (`database/schema.sql`)',
    '',
    '```sql',
    $schema.TrimEnd(),
    '```',
    '',
    '## App Config (`config/app.php`)',
    '',
    '```php',
    $configApp.TrimEnd(),
    '```',
    '',
    '## DB Config (`config/database.php`)',
    '',
    '```php',
    $configDb.TrimEnd(),
    '```',
    ''
) -join "`n"
Write-Utf8NoBom -Path (Join-Path $docsDir '09_SCHEMA_AND_CONFIG.md') -Content $schemaMd

# 10_VIEW_MAP.md
$viewFiles = Get-ChildItem -LiteralPath (Join-Path $root 'app/Views') -Recurse -File | Sort-Object FullName
$controllerRenderMap = @{}
$controllerSources = Get-ChildItem -LiteralPath (Join-Path $root 'app/Controllers') -File
foreach ($cf in $controllerSources) {
    $text = Get-Content -LiteralPath $cf.FullName -Raw
    $renderMatches = [regex]::Matches($text, "render\('([^']+)'")
    $controllerName = [System.IO.Path]::GetFileNameWithoutExtension($cf.Name)

    foreach ($rm in $renderMatches) {
        $viewName = $rm.Groups[1].Value.Replace('.', '/') + '.php'
        if (-not $controllerRenderMap.ContainsKey($viewName)) {
            $controllerRenderMap[$viewName] = New-Object System.Collections.Generic.List[string]
        }
        if (-not $controllerRenderMap[$viewName].Contains($controllerName)) {
            $controllerRenderMap[$viewName].Add($controllerName)
        }
    }
}

$viewLines = @('# 10 - View Map', '', '| View File | Used By Controller(s) |', '|---|---|')
foreach ($vf in $viewFiles) {
    $rel = (Get-RelativePath -BasePath (Join-Path $root 'app/Views') -TargetPath $vf.FullName).Replace('\', '/')
    $controllers = if ($controllerRenderMap.ContainsKey($rel)) { ($controllerRenderMap[$rel] -join ', ') } else { '(layout/partial or indirect)' }
    $viewLines += "| app/Views/$rel | $controllers |"
}
$viewLines += ''
Write-Utf8NoBom -Path (Join-Path $docsDir '10_VIEW_MAP.md') -Content ($viewLines -join "`n")

# 11_PUBLIC_ASSETS_AND_LAYOUT.md
$jsText = Get-Content -LiteralPath (Join-Path $root 'public/assets/js/app.js') -Raw
$setupMatches = [regex]::Matches($jsText, '(?m)^\s*const\s+(setup[a-zA-Z0-9_]+)\s*=\s*\(\)\s*=>\s*\{')
$setupNames = $setupMatches | ForEach-Object { $_.Groups[1].Value }

$assetLines = @(
    '# 11 - Public Assets And Layout',
    '',
    '## JS Modules (`public/assets/js/app.js`)',
    '',
    'Khoi tao module front-end hien co:',
    ''
)
foreach ($name in $setupNames) {
    $assetLines += "- " + $name
}
$assetLines += ''
$assetLines += '## CSS'
$assetLines += ''
$assetLines += '- Main stylesheet: `public/assets/css/app.css`'
$assetLines += '- Chua style cho auth, workspace, landing, create/preview quiz, exam, leaderboard va component chung.'
$assetLines += ''
$assetLines += '## Main Layout (`app/Views/layout/main.php`)'
$assetLines += ''
$assetLines += '- Co sidebar menu chung cho user da dang nhap.'
$assetLines += '- Neu role = admin, menu bo sung: `/users`, `/questions`.'
$assetLines += '- Header/topbar + flash message + csrf hidden input cho logout form.'
$assetLines += ''
Write-Utf8NoBom -Path (Join-Path $docsDir '11_PUBLIC_ASSETS_AND_LAYOUT.md') -Content ($assetLines -join "`n")

# 12_RUNTIME_STORAGE_SCRIPTS.md
$runtimeLines = @(
    '# 12 - Runtime Storage Scripts Backups',
    '',
    '## Runtime/State directories',
    '',
    '- `storage/database.sqlite` (hien co trong repo, co the la artifact local).',
    '- `storage/logs/` (app log + snapshot html).',
    '- `storage/sessions/` (PHP session file, save_path custom).',
    '- `storage/uploads/` (file nguon upload: txt/docx/pdf).',
    '',
    '## Script folder (`scripts/`)',
    '',
    '- `init_db.php`: tao DB + chay schema.sql.',
    '- `verify_question_parser.php`: test parser tren folder du lieu test.',
    '- `init_local_xampp.ps1`: khoi tao env local + init DB.',
    '- `start_local_xampp.ps1`: start built-in php server (`-t public`).',
    '',
    '## Backup/Test data in repo root',
    '',
    '- `backup/` va `project_backups/`: cac file zip backup local.',
    '- `du lieu test/`: bo file test parser nhan dien cau hoi.',
    '',
    '## Important notes',
    '',
    '- Repo dang gom ca source code + artifact runtime + backup zip.',
    '- Neu can clean source-only branch, nen tach artifact runtime ra khoi VCS.',
    ''
)
Write-Utf8NoBom -Path (Join-Path $docsDir '12_RUNTIME_STORAGE_SCRIPTS.md') -Content ($runtimeLines -join "`n")

# 13_WORKFLOWS.md
$workflowLines = @(
    '# 13 - End To End Workflows',
    '',
    '## Auth flow',
    '',
    '1. Guest vao `/login` hoac `/register`.',
    '2. Controller validate + AuthService thao tac session key `auth_user_id`.',
    '3. Sau login/register redirect ve `/workspace`.',
    '',
    '## Upload document flow',
    '',
    '1. POST `/documents` (csrf + file validate + ext + size).',
    '2. Luu file vao `storage/uploads`.',
    '3. `DocumentTextExtractorService` trich text (txt/docx/pdf).',
    '4. Repository tao row `documents`.',
    '5. Redirect sang `/quizzes/create`.',
    '',
    '## Import parser -> preview draft flow',
    '',
    '1. POST `/quizzes` voi `title + document_id`.',
    '2. `QuizGenerationService::extractQuestionsFromDocument()` parse bo cau hoi tu noi dung tai lieu (khong AI random).',
    '3. Draft luu trong session key `quiz_generation_draft`.',
    '4. GET `/quizzes/preview` de sua danh sach cau hoi.',
    '',
    '## AI suggestion flow (explicit only)',
    '',
    '1. Tu preview, POST `/quizzes/preview/suggest-ai`.',
    '2. AI tao `suggested_questions` de tac gia tick chon them vao de.',
    '3. Khong thay the danh sach import chinh; chi bo sung neu tac gia xac nhan.',
    '',
    '## Save quiz flow',
    '',
    '1. POST `/quizzes/preview/save`.',
    '2. JS serializes question list vao `questions_payload` JSON (de tranh max_input_vars).',
    '3. Controller validate cau hoi + merge voi suggestion duoc tick.',
    '4. Repository transaction create `quizzes` + `questions`.',
    '',
    '## Take + submit flow',
    '',
    '1. GET `/quizzes/{id}/take` hien thi de + progress + timer.',
    '2. POST `/quizzes/{id}/submit`.',
    '3. `SubmissionEvaluationService` tinh score/total_correct.',
    '4. Repository transaction create `submissions` + `submission_answers`.',
    '5. Redirect `/submissions/{id}`.',
    '',
    '## Leaderboard flow',
    '',
    '1. GET `/leaderboard` (optional `quiz_id`).',
    '2. Sap xep: score desc -> total_correct desc -> created_at asc -> id asc.',
    ''
)
Write-Utf8NoBom -Path (Join-Path $docsDir '13_WORKFLOWS.md') -Content ($workflowLines -join "`n")

# 00_INDEX.md
$indexLines = @(
    '# Repo Structure Docs Index',
    '',
    'Bo tai lieu nay mo ta cau truc chi tiet cua toan bo repo hien tai.',
    '',
    '## Files',
    '',
    '- [01_REPO_TREE.md](01_REPO_TREE.md)',
    '- [02_FILE_MANIFEST.md](02_FILE_MANIFEST.md)',
    '- [03_ARCHITECTURE_OVERVIEW.md](03_ARCHITECTURE_OVERVIEW.md)',
    '- [04_ROUTE_MAP.md](04_ROUTE_MAP.md)',
    '- [05_CONTROLLER_MAP.md](05_CONTROLLER_MAP.md)',
    '- [06_SERVICE_MAP.md](06_SERVICE_MAP.md)',
    '- [07_CORE_SUPPORT_EXCEPTION_MAP.md](07_CORE_SUPPORT_EXCEPTION_MAP.md)',
    '- [08_REPOSITORY_MAP.md](08_REPOSITORY_MAP.md)',
    '- [09_SCHEMA_AND_CONFIG.md](09_SCHEMA_AND_CONFIG.md)',
    '- [10_VIEW_MAP.md](10_VIEW_MAP.md)',
    '- [11_PUBLIC_ASSETS_AND_LAYOUT.md](11_PUBLIC_ASSETS_AND_LAYOUT.md)',
    '- [12_RUNTIME_STORAGE_SCRIPTS.md](12_RUNTIME_STORAGE_SCRIPTS.md)',
    '- [13_WORKFLOWS.md](13_WORKFLOWS.md)',
    '',
    '## Scope',
    '',
    '- Snapshot theo codebase tai thoi diem tao tai lieu.',
    '- Bao gom source code, scripts, storage artifacts, backups va du lieu test hien co trong repo.',
    ''
)
Write-Utf8NoBom -Path (Join-Path $docsDir '00_INDEX.md') -Content ($indexLines -join "`n")

Write-Output 'DONE: generated docs/repo-structure/*.md'
