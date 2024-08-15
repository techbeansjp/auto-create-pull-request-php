<?php

// logs ディレクトリ内のファイルを全て空にする
$logsDir = __DIR__ . '/logs';
if (is_dir($logsDir)) {
    $files = glob($logsDir . '/*');
    foreach ($files as $file) {
        if (is_file($file)) {
            file_put_contents($file, '');
            echo date('Y-m-d H:i:s') . " - ファイルを空にしました: " . basename($file) . "\n";
        }
    }
} else {
    echo date('Y-m-d H:i:s') . " - logs ディレクトリが見つかりません。\n";
}


// .envファイルからAPI_KEYを取得
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $envContents = file_get_contents($envFile);
    $matches = [];
    if (preg_match('/API_KEY="([^"]+)"/', $envContents, $matches) && preg_match('/TARGET_BRANCH="([^"]+)"/', $envContents, $targetMatches)) {
        $apiKey = $matches[1];
        $targetBranch = $targetMatches[1];
        echo date('Y-m-d H:i:s') . " - GET API KEY: Success\n";
        echo date('Y-m-d H:i:s') . " - GET TARGET BRANCH: Success\n";
    } else {
        echo date('Y-m-d H:i:s') . " - API_KEYまたはTARGET_BRANCHが見つかりませんでした。\n";
        exit(1);
    }
} else {
    echo date('Y-m-d H:i:s') . " - .envファイルが見つかりません。\n";
    exit(1);
}

// 現在のブランチ名を取得
$currentBranch = trim(shell_exec('git rev-parse --abbrev-ref HEAD'));

// マージ先ブランチとの差分を取得
$diffCommand = "git diff origin/$targetBranch $currentBranch";
echo date('Y-m-d H:i:s') . " - GET DIFF: $diffCommand\n";
$diff = shell_exec($diffCommand);

// diffをログファイルに保存
$diffLogFile = __DIR__ . '/logs/diff.txt';

// ディレクトリが存在しない場合は作成
if (!is_dir(dirname($diffLogFile))) {
    mkdir(dirname($diffLogFile), 0777, true);
}

// diffをファイルに書き込む
file_put_contents($diffLogFile, $diff);


// $targetBranchのソースツリーを取得（vendor, node_modulesを除外）
$sourceTree = shell_exec("cd .. && tree -L 5 -I 'vendor|node_modules' --charset=ascii");

// ログファイルのパスを設定
$logFile = __DIR__ . '/logs/pr_description.txt';

// $sourceTreeをログファイルに保存
$sourceTreeLogFile = __DIR__ . '/logs/source_tree.txt';

// ディレクトリが存在しない場合は作成
if (!is_dir(dirname($sourceTreeLogFile))) {
    mkdir(dirname($sourceTreeLogFile), 0777, true);
}

// $sourceTreeをファイルに書き込む
file_put_contents($sourceTreeLogFile, $sourceTree);

echo date('Y-m-d H:i:s') . " - ソースツリーをファイルに保存しました: $sourceTreeLogFile\n";

// ファイルの内容を確認
if (file_exists($sourceTreeLogFile)) {
    $content = file_get_contents($sourceTreeLogFile);
    if (!empty($content)) {
        echo date('Y-m-d H:i:s') . " - ソースツリーファイルの内容を確認しました。\n";
    } else {
        echo date('Y-m-d H:i:s') . " - 警告: ソースツリーファイルが空です。\n";
    }
} else {
    echo date('Y-m-d H:i:s') . " - エラー: ソースツリーファイルが作成されませんでした。\n";
}


// pull-request-template.mdファイルを読み込む
$templateFile = __DIR__ . '/pull-request-template.md';
if (file_exists($templateFile)) {
    $template = file_get_contents($templateFile);
    echo date('Y-m-d H:i:s') . " - GET TEMPLATE: Success\n";
} else {
    echo date('Y-m-d H:i:s') . " - pull-request-template.mdファイルが見つかりません。\n";
    exit(1);
}


// Claude APIにリクエストする処理

// APIエンドポイントとヘッダーの設定
$apiEndpoint = 'https://api.anthropic.com/v1/messages';
$headers = [
    'Content-Type: application/json',
    'x-api-key: ' . $apiKey,
    'anthropic-version: 2023-06-01'
];

$content = "以下の差分とソースツリーを分析し、プルリクエストの説明文を生成してください。テンプレートとして以下を使用し、各セクションに適切な内容を記入してください:\n\n$template\n\n差分:\n$diff\n\nソースツリー:\n$sourceTree";

// $contentの内容をログファイルに出力
$contentLogFile = __DIR__ . '/logs/content.log';

// ディレクトリが存在しない場合は作成
if (!is_dir(dirname($contentLogFile))) {
    mkdir(dirname($contentLogFile), 0777, true);
}

// $contentをファイルに書き込む
file_put_contents($contentLogFile, $content);

echo date('Y-m-d H:i:s') . " - \$contentの内容をログファイルに保存しました: $contentLogFile\n";

// ファイルの内容を確認
if (file_exists($contentLogFile)) {
    $loggedContent = file_get_contents($contentLogFile);
    if (!empty($loggedContent)) {
        echo date('Y-m-d H:i:s') . " - \$contentのログファイルの内容を確認しました。\n";
    } else {
        echo date('Y-m-d H:i:s') . " - 警告: \$contentのログファイルが空です。\n";
    }
} else {
    echo date('Y-m-d H:i:s') . " - エラー: \$contentのログファイルが作成されませんでした。\n";
}

// --dry-runオプションのチェック
if (in_array('--dry-run', $argv)) {
    echo date('Y-m-d H:i:s') . " - ドライランモードです。ここで処理を終了します。\n";
    exit(0);
}



// リクエストボディの作成
$requestBody = [
    'model' => 'claude-3-opus-20240229',
    'max_tokens' => 1000,
    'messages' => [
        [
            'role' => 'user',
            'content' => $content
        ]
    ]
];


// cURLセッションの初期化
$ch = curl_init($apiEndpoint);

// cURLオプションの設定
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestBody));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

// APIリクエストの実行
$response = curl_exec($ch);

// エラーチェック
if (curl_errno($ch)) {
    echo date('Y-m-d H:i:s') . ' - cURLエラー: ' . curl_error($ch);
    exit(1);
}

// セッションのクローズ
curl_close($ch);

// レスポンスの解析
$responseData = json_decode($response, true);

if (isset($responseData['content'][0]['text'])) {
    echo date('Y-m-d H:i:s') . " - GET PR DESCRIPTION: Success\n";
    $prDescription = $responseData['content'][0]['text'];
    // PRの説明文をファイルに出力
    $outputFile = __DIR__ . '/logs/pr_description.txt';

    if (file_put_contents($outputFile, $prDescription) !== false) {
        echo date('Y-m-d H:i:s') . " - PRの説明文が $outputFile に保存されました。\n";
    } else {
        echo date('Y-m-d H:i:s') . " - PRの説明文の保存に失敗しました。\n";
        // PRの説明文を出力
        echo date('Y-m-d H:i:s') . " - PRの説明文:\n";
        echo $prDescription;
    }
} else {
    echo date('Y-m-d H:i:s') . " - APIレスポンスの解析に失敗しました。\n";
    print_r($responseData);
}
