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
7. `docs/環境構築手順書.md` — 自前サーバーでの開発環境構築の実際の手順（コマンド付き）

ドキュメント間で矛盾・抜けを見つけた場合は、実装より先にドキュメントを修正するPRを作成する（開発ガイドライン6章参照）。

## 開発体制

- 開発者2名（山本・奥野）による共同開発。
- 作業は `main` から派生したブランチ（`feature/*`・`fix/*`・`docs/*`）で行い、Pull Requestを経て `main` にマージする（リポジトリ構造定義書5章参照）。
- 2名が並行して別ブランチで作業するため、着手前に `main` を取り込み、マイグレーションファイルのタイムスタンプ・テーブル変更の重複や衝突がないか確認する。
- 相手が作業中のブランチを直接pushで上書きしない。レビュー・マージの合意はPR上で行う。

### 担当分担

- **山本**：車両マスタ（`VehicleController`／`Masters/Vehicles.vue`）
- **奥野**：車両マスタ以外の全工程

以下の順序で作成する（データベース→職員マスタ→ログイン画面→残り3マスタ→予約・シフト申請→予約一覧→承認）。

1. **データベース**（奥野）：全テーブルのマイグレーション一式（技術仕様書4章）
2. **職員マスタ**（奥野）：`StaffController`／`Masters/Staff.vue`。ログイン認証が職員データを参照するため、ログイン画面より先に用意する
3. **ログイン画面**（奥野）：`AuthController`／`Auth/Login.vue`、アカウントロック判定・ログインログ（技術仕様書6章）
4. **残り3マスタ**：
   - 奥野：`ClientController`／`Masters/Clients.vue`（利用者マスタ）、`SupportTypeController`／`Masters/SupportTypes.vue`（支援内容マスタ）
   - 山本：`VehicleController`／`Masters/Vehicles.vue`（車両マスタ）
5. **予約・シフト申請**（奥野）：シフト申請フォーム（`ShiftController`／`Shifts/Form.vue`、6.5参照）、予約申請フォーム＋空き日時検索（`ReservationController`／`Reservations/Create.vue`、`VehicleAssignmentService`、6.4参照）
6. **予約一覧（トップ画面）**（奥野）：`Reservations/Index.vue`（6.3参照）
7. **承認画面**（奥野）：`ApprovalController`／`Approvals/Index.vue`（6.6参照）

この順序には含まれていないが、実装時に必要になる項目（いずれも奥野）：
- 監査ログ（`AuditLogger`、技術仕様書5.3）
- 権限制御：`ShiftPolicy`／`ReservationPolicy`（リポジトリ構造定義書2.7参照）
- 予約忘れ検知バッチ（`MissedBookingDetectionService`、技術仕様書5.2）
- 非機能要件の最終確認（パスワードポリシー・セッションタイムアウト・SSL/TLS等）

## テストサーバー

テストサーバーは、自前で用意したサーバーにSSHで接続して構築する（本番ホスティング環境はXserverを想定、技術仕様書1章参照。テストサーバーと本番環境は別物）。開発者2名がそれぞれ個別のSSHユーザーで同一サーバーに接続し、作業する。開発者個人のPC（Windows・WSL2）での開発環境構築は `docs/環境構築手順書.md` の「3. 開発環境固有の手順」を参照。

### サーバーに必要な環境

技術仕様書1章の技術スタック（PHP 8.3 / Laravel 13、Inertia.js v3 + Vue.js 3.5 + Tailwind CSS 4、MySQL 8.4 LTS）を動かすため、サーバー側に以下を導入する。

| 分類 | 必要なもの | 備考 |
|---|---|---|
| OS | Linux（Ubuntu 26.04 LTS等） | 開発者間でバージョンを揃える |
| PHP | 8.3系 | Xserver推奨バージョンのため採用（技術仕様書1章参照）。拡張：mbstring, xml, curl, zip, pdo_mysql, bcmath, gd, intl, fileinfo |
| Composer | 2.10系 | PHP依存パッケージ管理 |
| Node.js | 24系（LTS） | npm経由でVue.js・Tailwind CSSをビルド（`npm run build`／`npm run dev`） |
| MySQL | 8.4系（LTS） | 開発用DBサーバー（本番と同バージョンに揃える） |
| Webサーバー | Nginx + PHP-FPM、または `php artisan serve` | 開発中は`serve`で簡易起動も可 |
| Git | 最新版 | バージョン管理 |
| SSH | 開発者2名分の個別ユーザーアカウント・公開鍵登録 | rootアカウントの共用は避ける |

具体的な導入コマンド・セットアップ手順は `docs/環境構築手順書.md` を参照。
