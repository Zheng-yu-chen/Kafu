# 系統項目資料庫單元測試文檔

## 概述
本檔案包含針對 `system_project (1).sql` 資料庫架構的完整單元測試套件。測試使用 PHPUnit 框架編寫，驗證資料庫的結構、約束、關係和初始數據的完整性。

## 前置要求
- PHP 8.0.30 或以上版本
- MariaDB 10.4.32 或以上版本
- PHPUnit 9.0 或以上版本
- Composer（用於安裝 PHPUnit）

## 文件結構
```
kafu/
├── system_project (1).sql          # 資料庫 SQL 架構檔案
├── SystemProjectDatabaseTest.php   # 單元測試檔案
├── phpunit.xml                     # PHPUnit 配置檔案
└── DATABASE_TEST_README.md         # 本文檔
```

## 安裝步驟

### 1. 安裝 PHPUnit
```bash
composer require --dev phpunit/phpunit ^9.0
```

### 2. 創建資料庫
在 MariaDB 中執行 SQL 檔案以創建資料庫和表：
```bash
mysql -h 127.0.0.1 -P 3386 -u root < "system_project (1).sql"
```

### 3. 配置測試檔案
編輯 `SystemProjectDatabaseTest.php` 中的數據庫連接設置：
```php
private $db_host = '127.0.0.1:3386';
private $db_user = 'root';
private $db_password = '';
private $db_name = 'system_project';
```

## 運行測試

### 運行所有測試
```bash
vendor/bin/phpunit
```

### 運行特定測試
```bash
vendor/bin/phpunit --filter testAccountsTableExists
```

### 生成測試報告
```bash
vendor/bin/phpunit --coverage-html=coverage
```

## 測試內容

### 表結構測試 (Table Structure Tests)
- `testAccountsTableExists()` - 驗證 accounts 表存在
- `testPermissionsTableExists()` - 驗證 permissions 表存在
- `testRestaurantsTableExists()` - 驗證 restaurants 表存在
- `testAccountsTableStructure()` - 驗證 accounts 表的所有字段
- `testPermissionsTableStructure()` - 驗證 permissions 表的所有字段
- `testRestaurantsTableStructure()` - 驗證 restaurants 表的所有字段

### 約束測試 (Constraint Tests)
- `testAccountsPrimaryKey()` - 驗證 accounts 表的主鍵
- `testPermissionsPrimaryKey()` - 驗證 permissions 表的主鍵
- `testRestaurantsPrimaryKey()` - 驗證 restaurants 表的主鍵
- `testAccountsUniqueConstraint()` - 驗證 accounts 字段的唯一約束
- `testUniqueConstraintAccounts()` - 測試唯一約束的執行

### 外鍵測試 (Foreign Key Tests)
- `testAccountsForeignKeyRole()` - 驗證 accounts.role_id 外鍵
- `testAccountsForeignKeyRestaurant()` - 驗證 accounts.r_id 外鍵
- `testForeignKeyConstraintRoleId()` - 測試無效 role_id 的拒絕
- `testForeignKeyConstraintRestaurantId()` - 測試無效 r_id 的拒絕

### 初始數據測試 (Data Integrity Tests)
- `testAdminAccountExists()` - 驗證管理員帳戶
- `testStaffAccountExists()` - 驗證店員帳戶
- `testStudentAccountsExist()` - 驗證學生帳戶
- `testAdminPermissions()` - 驗證管理員權限設置
- `testStudentPermissions()` - 驗證學生權限設置
- `testRestaurantsDataExists()` - 驗證餐廳初始數據
- `testSpecificRestaurantExists()` - 驗證特定餐廳數據

### 其他測試 (Other Tests)
- `testCreatedAtDefaultValue()` - 驗證 created_at 預設值
- `testDatabaseCharset()` - 驗證資料庫字符集為 UTF-8
- `testTablesUseInnoDBEngine()` - 驗證所有表使用 InnoDB 引擎

## 數據庫架構摘要

### accounts 表
- **u_id** (PK): 使用者ID，自動遞增
- **name**: 使用者名稱
- **accounts**: 帳號（唯一）
- **password**: 密碼
- **role_id** (FK): 角色ID，參考 permissions.role_id
- **r_id** (FK): 餐廳ID，參考 restaurants.r_id
- **created_at**: 創建時間，預設值為當前時間

### permissions 表
- **role_id** (PK): 角色ID
- **role**: 角色名稱
- **p_record**: 記錄權限
- **p_comment**: 評論權限
- **p_manage**: 管理權限
- **p_audit**: 審核權限
- **system_permissions**: 系統權限說明

### restaurants 表
- **r_id** (PK): 餐廳ID，自動遞增
- **name**: 餐廳名稱
- **location**: 餐廳位置
- **description**: 餐廳描述

## 初始數據概覽

### 用戶角色
- **admin (1)**: 最高權限 - 審核評論、處理報錯、管理所有資料
- **staff (2)**: 營運者 - 更新菜單、發布公告、觀看評論
- **student (3)**: 核心用戶 - 記錄攝取量、設定目標、參與評論
- **visitor (4)**: 僅能操作計算機與轉盤

### 初始用戶
- admin (ID: 1)
- staff (ID: 2)
- 學生 A 和 B (ID: 3, 4)

### 餐廳
初始包含 19 家餐廳，包括：
- 心園校區：巧瑋鬆餅屋、心園麵店、心園自助餐
- 理園校區：辛蔬料理、澳門華記、娃子早餐店等
- 輔園校區：食福簡餐、深川味、埃及教父等

## 故障排除

### 連接失敗
- 確認 MariaDB 服務正在運行
- 檢查主機、埠號和認證信息
- 確認資料庫已創建

### 表不存在
- 重新執行 SQL 檔案以創建表結構
- 確認 SQL 檔案執行時沒有錯誤

### 字符編碼問題
- 確認資料庫使用 utf8mb4 字符集
- 檢查 PHP 連接的字符集設置

### 外鍵約束錯誤
- 確認參考的主鍵記錄存在
- 檢查資料庫引擎是否為 InnoDB

## 最佳實踐

1. **定期運行測試**: 在修改資料庫架構後運行測試
2. **備份數據**: 測試涉及插入和刪除操作前備份
3. **獨立的測試環境**: 使用專用的測試資料庫，不要在生產資料庫上運行
4. **監控測試結果**: 檢查測試覆蓋率並改進不足之處
5. **文檔化更改**: 添加新表或字段時更新測試

## 擴展測試

可以根據需要添加以下測試：
- 性能測試：大量數據插入測試
- 數據驗證測試：檢查字段值的有效性
- 業務邏輯測試：驗證複雜查詢的結果
- 並發測試：測試多個用戶同時訪問

## 指導支持

如遇到問題，請檢查：
1. PHPUnit 是否正確安裝
2. 資料庫連接參數是否正確
3. 資料庫和表是否存在
4. PHP MySQLi 擴展是否啟用

---
**最後更新**: 2026-04-14
**測試框架**: PHPUnit 9.0+
**資料庫**: MariaDB 10.4.32+
