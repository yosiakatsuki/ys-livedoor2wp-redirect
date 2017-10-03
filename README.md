## YS Livedoor2WP Redirect

ライブドアブログ時代のURLを上手いことWordPress時代のURLにリダイレクトするプラグイン

### 前提条件

- ライブドアブログからエクスポートした記事データをWordPressにインポートしていることが前提となります。
	- ライブドアブログの時のURLの一部（数字部分）がWordPressの記事スラッグとしてインポートされるため
- 独自のURL構造にしていた場合は`BASENAME:`ではなく`PATH:`からスラッグを作るようにインポーターを改造して記事インポートして下さい
	- 例：`http://example.com/archives/post-name.html` -> `post-name`がスラッグとなるように

### 使い方

- プラグインをFTP、もしくはzip圧縮したファイルをプラグインの新規追加からアップロードします
- プラグインを有効にして下さい
	- 設定等はありません

### 効能

ライブドアブログ時代のURL:`http://example.com/archives/1234567890.html`でアクセスされ、ページが見つからなかった時、
WordPress時代のURL`http://example.com/1234567890/`で記事が見つかれば301リダイレクトします。

ドメインそのままライブドアブログ -> WordPress への移転の際にご利用下さい。

### その他

[設定] -> [ライブドアブログ->WP] で最近のリダイレクト状況を見ることができます（簡易的に）
直近1ヶ月以内にリダイレクトが発生していなければプラグインは削除してもかまわないと思います。
