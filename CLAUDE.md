# CLAUDE.md

このリポジトリで作業する際の前提情報をまとめる。実装に着手する前に、必ず `docs/` 配下の資料に目を通すこと。

## プロジェクト概要

ヘルプステーション（訪問看護サービス）向けの、シフト管理・利用者予約管理システム。詳細は `docs/要件定義書.md` を参照。

## 資料一覧（読む順序）

1. `docs/要件定義書.md` — 何を作るか（機能要件・非機能要件・意思決定履歴）
2. `docs/基本設計書.md` — 画面・DBの論理設計
3. `docs/技術仕様書.md` — 技術スタック・DB物理設計・実装方針
4. `docs/リポジトリ構造定義書.md` — ディレクトリ構成・命名規則・Git運用ルール
5. `docs/開発ガイドライン.md` — コーディング規約・アーキテクチャ方針・テスト方針
6. `docs/ユビキタス言語定義書.md` — 業務用語とコード上の識別子の対応表（用語は必ずこれに従う）

ドキュメント間で矛盾・抜けを見つけた場合は、実装より先にドキュメントを修正するPRを作成する（開発ガイドライン6章参照）。

## 開発体制

- 開発者2名による共同開発。
- 作業は `main` から派生したブランチ（`feature/*`・`fix/*`・`docs/*`）で行い、Pull Requestを経て `main` にマージする（リポジトリ構造定義書5章参照）。
- 2名が並行して別ブランチで作業するため、着手前に `main` を取り込み、マイグレーションファイルのタイムスタンプ・テーブル変更の重複や衝突がないか確認する。
- 相手が作業中のブランチを直接pushで上書きしない。レビュー・マージの合意はPR上で行う。

### 担当分担（案）

機能単位で分担し、両者に依存する基盤部分は先に共同で片付ける。開発者A／Bは仮称で、実際の割り振りは各自の得意領域に応じて入れ替えてよい。ファイル・クラス名はリポジトリ構造定義書2章に対応する。

**フェーズ0（共同・先行して着手）**
- DBマイグレーション一式：`staff`, `staff_default_schedules`, `clients`, `client_default_staff`, `client_fixed_days_of_week`, `support_types`, `vehicles`, `shifts`, `shift_details`, `reservations`, `reservation_staff`（技術仕様書4章）
- 認証基盤：`AuthController`（ログイン画面）、ログイン成功・失敗ログ、アカウントロック判定（`failed_login_count`等）、セッションタイムアウト設定（技術仕様書6章）
- マスタ共通処理：`HasSoftDeletableMaster`トレイト（一覧・登録・変更・削除・削除済み一覧・復元の共通ロジック、リポジトリ構造定義書2.2参照）
- 4マスタのController・Vueページは2名で分担：
  - 開発者A：`StaffController`／`Masters/Staff.vue`、`SupportTypeController`／`Masters/SupportTypes.vue`
  - 開発者B：`ClientController`／`Masters/Clients.vue`、`VehicleController`／`Masters/Vehicles.vue`

**フェーズ1（機能単位で分担）**
- 開発者A：シフト管理機能
  - Model：`Shift`、`ShiftDetail`、`StaffDefaultSchedule`
  - Controller／Vue：`ShiftController`／`Shifts/Form.vue`（6.5 シフト申請フォーム）
  - ロジック：デフォルト勤務時間の初期表示、当月労働時間合計・年間労働時間上限に対する残り時間の計算、「申請後は変更不可」制御
  - アラート：シフト未申請アラート・シフト未承認アラートの判定（ログインコントローラー拡張、6.2参照）
- 開発者B：予約管理機能
  - Model：`Reservation`、`reservation_staff`中間テーブルのリレーション
  - Controller／Vue：`ReservationController`／`Reservations/Index.vue`・`Reservations/Create.vue`（6.4 予約申請フォーム・空き日時検索3パターンのタブ切替、`GET /reservations/available-slots`）
  - Service：`VehicleAssignmentService`（配車優先度・車両空き判定・再割当、技術仕様書5.1）
  - アラート：予約未承認アラートの判定（6.2参照）

**フェーズ2（共同・統合）**
- 承認画面：`ApprovalController`／`Approvals/Index.vue`（6.6、シフト・予約共通の画面のため、フェーズ1の両実装がそろってから一緒に仕上げる。開発者Aがシフト編集・承認部分、開発者Bが予約編集・削除・承認部分を担当し、画面統合のみ共同作業とする案でよい）
- 権限制御：`ShiftPolicy`（開発者A）、`ReservationPolicy`（開発者B）（リポジトリ構造定義書2.7参照）
- 監査ログ：`AuditLogger`（技術仕様書5.3。全操作から共通で呼び出されるため、フェーズ1と並行してどちらかが先行実装してもよい）
- 予約忘れ検知バッチ：`MissedBookingDetectionService`／`DetectMissedBookings`コマンド（技術仕様書5.2、開発者Bが予約領域の延長として担当する案でよい）
- 非機能要件の最終確認：パスワードポリシー・セッションタイムアウト・SSL/TLS等（技術仕様書6章）
- Feature Test（画面・操作単位の受け入れテスト）は各自が自分の担当機能分を作成し、共通基盤（フェーズ0）分は共同で分担する

## 開発環境

開発環境は、自前で用意したサーバーにSSHで接続して構築する（本番ホスティング環境はXserverを想定、技術仕様書1章参照。開発環境と本番環境は別物）。開発者2名がそれぞれ個別のSSHユーザーで同一サーバーに接続し、作業する。

### サーバーに必要な環境

技術仕様書1章の技術スタック（PHP 8.3 / Laravel 11、Inertia.js v2 + Vue.js 3 + Tailwind CSS 3、MySQL 8.0）を動かすため、サーバー側に以下を導入する。

| 分類 | 必要なもの | 備考 |
|---|---|---|
| OS | Linux（Ubuntu 22.04 LTS等） | 開発者間でバージョンを揃える |
| PHP | 8.3系 | 拡張：mbstring, xml, curl, zip, pdo_mysql, bcmath, gd, intl, fileinfo |
| Composer | 2系（最新） | PHP依存パッケージ管理 |
| Node.js | 20系（LTS） | npm経由でVue.js・Tailwind CSSをビルド（`npm run build`／`npm run dev`） |
| MySQL | 8.0系 | 開発用DBサーバー（本番と同バージョンに揃える） |
| Webサーバー | Nginx + PHP-FPM、または `php artisan serve` | 開発中は`serve`で簡易起動も可 |
| Git | 最新版 | バージョン管理 |
| SSH | 開発者2名分の個別ユーザーアカウント・公開鍵登録 | rootアカウントの共用は避ける |

- `.env` は開発者ごとに用意し、Git管理対象外とする（リポジトリ構造定義書1.1参照）。
- 同一サーバー上で2名が同時に開発するため、作業ディレクトリ・DBスキーマ（例：`helpstation_dev_yamamoto`／`helpstation_dev_yamada`）・`php artisan serve`使用時のポート番号は開発者ごとに分け、衝突を避ける。
