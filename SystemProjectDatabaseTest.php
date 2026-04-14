<?php
/**
 * 系統項目資料庫單元測試
 * 測試對象：system_project (1).sql
 */

use PHPUnit\Framework\TestCase;

class SystemProjectDatabaseTest extends TestCase
{
    private $mysqli;
    private $db_host = '127.0.0.1:3386';
    private $db_user = 'root';
    private $db_password = '';
    private $db_name = 'system_project';

    protected function setUp(): void
    {
        // 建立資料庫連接
        $this->mysqli = new mysqli('127.0.0.1', $this->db_user, $this->db_password, $this->db_name, 3386);
        
        if ($this->mysqli->connect_error) {
            $this->fail('資料庫連接失敗：' . $this->mysqli->connect_error);
        }
        
        // 設置字符集
        $this->mysqli->set_charset('utf8mb4');
    }

    protected function tearDown(): void
    {
        if ($this->mysqli) {
            $this->mysqli->close();
        }
    }

    /**
     * 測試：accounts 表是否存在
     */
    public function testAccountsTableExists()
    {
        $result = $this->mysqli->query("SHOW TABLES LIKE 'accounts'");
        $this->assertGreaterThan(0, $result->num_rows, 'accounts 表不存在');
    }

    /**
     * 測試：permissions 表是否存在
     */
    public function testPermissionsTableExists()
    {
        $result = $this->mysqli->query("SHOW TABLES LIKE 'permissions'");
        $this->assertGreaterThan(0, $result->num_rows, 'permissions 表不存在');
    }

    /**
     * 測試：restaurants 表是否存在
     */
    public function testRestaurantsTableExists()
    {
        $result = $this->mysqli->query("SHOW TABLES LIKE 'restaurants'");
        $this->assertGreaterThan(0, $result->num_rows, 'restaurants 表不存在');
    }

    /**
     * 測試：accounts 表的字段結構
     */
    public function testAccountsTableStructure()
    {
        $result = $this->mysqli->query("DESCRIBE accounts");
        $columns = [];
        
        while ($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = $row;
        }

        // 驗證必需的字段存在
        $this->assertArrayHasKey('u_id', $columns, 'u_id 字段缺失');
        $this->assertArrayHasKey('name', $columns, 'name 字段缺失');
        $this->assertArrayHasKey('accounts', $columns, 'accounts 字段缺失');
        $this->assertArrayHasKey('password', $columns, 'password 字段缺失');
        $this->assertArrayHasKey('role_id', $columns, 'role_id 字段缺失');
        $this->assertArrayHasKey('r_id', $columns, 'r_id 字段缺失');
        $this->assertArrayHasKey('created_at', $columns, 'created_at 字段缺失');
    }

    /**
     * 測試：permissions 表的字段結構
     */
    public function testPermissionsTableStructure()
    {
        $result = $this->mysqli->query("DESCRIBE permissions");
        $columns = [];
        
        while ($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = $row;
        }

        // 驗證必需的字段存在
        $this->assertArrayHasKey('role_id', $columns, 'role_id 字段缺失');
        $this->assertArrayHasKey('role', $columns, 'role 字段缺失');
        $this->assertArrayHasKey('p_record', $columns, 'p_record 字段缺失');
        $this->assertArrayHasKey('p_comment', $columns, 'p_comment 字段缺失');
        $this->assertArrayHasKey('p_manage', $columns, 'p_manage 字段缺失');
        $this->assertArrayHasKey('p_audit', $columns, 'p_audit 字段缺失');
    }

    /**
     * 測試：restaurants 表的字段結構
     */
    public function testRestaurantsTableStructure()
    {
        $result = $this->mysqli->query("DESCRIBE restaurants");
        $columns = [];
        
        while ($row = $result->fetch_assoc()) {
            $columns[$row['Field']] = $row;
        }

        // 驗證必需的字段存在
        $this->assertArrayHasKey('r_id', $columns, 'r_id 字段缺失');
        $this->assertArrayHasKey('name', $columns, 'name 字段缺失');
        $this->assertArrayHasKey('location', $columns, 'location 字段缺失');
        $this->assertArrayHasKey('description', $columns, 'description 字段缺失');
    }

    /**
     * 測試：accounts 表的主鍵約束
     */
    public function testAccountsPrimaryKey()
    {
        $result = $this->mysqli->query("SHOW KEYS FROM accounts WHERE Key_name = 'PRIMARY'");
        $this->assertGreaterThan(0, $result->num_rows, 'accounts 表缺少主鍵');
    }

    /**
     * 測試：permissions 表的主鍵約束
     */
    public function testPermissionsPrimaryKey()
    {
        $result = $this->mysqli->query("SHOW KEYS FROM permissions WHERE Key_name = 'PRIMARY'");
        $this->assertGreaterThan(0, $result->num_rows, 'permissions 表缺少主鍵');
    }

    /**
     * 測試：restaurants 表的主鍵約束
     */
    public function testRestaurantsPrimaryKey()
    {
        $result = $this->mysqli->query("SHOW KEYS FROM restaurants WHERE Key_name = 'PRIMARY'");
        $this->assertGreaterThan(0, $result->num_rows, 'restaurants 表缺少主鍵');
    }

    /**
     * 測試：accounts 表的外鍵 fk_acc_role
     */
    public function testAccountsForeignKeyRole()
    {
        $result = $this->mysqli->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                                       WHERE TABLE_NAME = 'accounts' AND COLUMN_NAME = 'role_id' 
                                       AND REFERENCED_TABLE_NAME = 'permissions'");
        $this->assertGreaterThan(0, $result->num_rows, 'accounts.role_id 的外鍵不存在');
    }

    /**
     * 測試：accounts 表的外鍵 fk_acc_res
     */
    public function testAccountsForeignKeyRestaurant()
    {
        $result = $this->mysqli->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                                       WHERE TABLE_NAME = 'accounts' AND COLUMN_NAME = 'r_id' 
                                       AND REFERENCED_TABLE_NAME = 'restaurants'");
        $this->assertGreaterThan(0, $result->num_rows, 'accounts.r_id 的外鍵不存在');
    }

    /**
     * 測試：accounts 表中的唯一約束 (accounts 字段)
     */
    public function testAccountsUniqueConstraint()
    {
        $result = $this->mysqli->query("SHOW KEYS FROM accounts WHERE Key_name = 'accounts'");
        $this->assertGreaterThan(0, $result->num_rows, 'accounts 字段的唯一約束不存在');
    }

    /**
     * 測試：驗證初始數據 - 管理員帳戶
     */
    public function testAdminAccountExists()
    {
        $result = $this->mysqli->query("SELECT * FROM accounts WHERE u_id = 1 AND accounts = 'admin'");
        $this->assertEquals(1, $result->num_rows, '管理員帳戶不存在或數據不正確');
        
        $row = $result->fetch_assoc();
        $this->assertEquals('管理員', $row['name']);
        $this->assertEquals(1, $row['role_id']);
    }

    /**
     * 測試：驗證初始數據 - 店員帳戶
     */
    public function testStaffAccountExists()
    {
        $result = $this->mysqli->query("SELECT * FROM accounts WHERE u_id = 2 AND accounts = 'staff'");
        $this->assertEquals(1, $result->num_rows, '店員帳戶不存在或數據不正確');
        
        $row = $result->fetch_assoc();
        $this->assertEquals('店員', $row['name']);
        $this->assertEquals(2, $row['role_id']);
    }

    /**
     * 測試：驗證初始數據 - 學生帳戶數量
     */
    public function testStudentAccountsExist()
    {
        $result = $this->mysqli->query("SELECT COUNT(*) as count FROM accounts WHERE role_id = 3");
        $row = $result->fetch_assoc();
        $this->assertGreaterThanOrEqual(2, $row['count'], '學生帳戶數量不足');
    }

    /**
     * 測試：驗證權限數據 - 管理員權限
     */
    public function testAdminPermissions()
    {
        $result = $this->mysqli->query("SELECT * FROM permissions WHERE role_id = 1");
        $this->assertEquals(1, $result->num_rows, '管理員權限記錄不存在');
        
        $row = $result->fetch_assoc();
        $this->assertEquals('admin', $row['role']);
        $this->assertEquals(1, $row['p_record']);
        $this->assertEquals(1, $row['p_comment']);
        $this->assertEquals(1, $row['p_manage']);
        $this->assertEquals(1, $row['p_audit']);
    }

    /**
     * 測試：驗證權限數據 - 學生權限
     */
    public function testStudentPermissions()
    {
        $result = $this->mysqli->query("SELECT * FROM permissions WHERE role_id = 3");
        $this->assertEquals(1, $result->num_rows, '學生權限記錄不存在');
        
        $row = $result->fetch_assoc();
        $this->assertEquals('student', $row['role']);
        $this->assertEquals(1, $row['p_record']);
        $this->assertEquals(1, $row['p_comment']);
        $this->assertEquals(0, $row['p_manage']);
        $this->assertEquals(0, $row['p_audit']);
    }

    /**
     * 測試：驗證餐廳數據 - 餐廳數量
     */
    public function testRestaurantsDataExists()
    {
        $result = $this->mysqli->query("SELECT COUNT(*) as count FROM restaurants");
        $row = $result->fetch_assoc();
        $this->assertGreaterThanOrEqual(19, $row['count'], '初始餐廳數據不足');
    }

    /**
     * 測試：驗證餐廳數據 - 巧瑋鬆餅屋
     */
    public function testSpecificRestaurantExists()
    {
        $result = $this->mysqli->query("SELECT * FROM restaurants WHERE r_id = 1 AND name = '巧瑋鬆餅屋'");
        $this->assertEquals(1, $result->num_rows, '巧瑋鬆餅屋餐廳數據不正確');
        
        $row = $result->fetch_assoc();
        $this->assertEquals('心園', $row['location']);
    }

    /**
     * 測試：驗證外鍵約束 - 無效的 role_id
     */
    public function testForeignKeyConstraintRoleId()
    {
        // 嘗試插入無效的 role_id
        $result = $this->mysqli->query("INSERT INTO accounts (u_id, name, accounts, password, role_id) 
                                      VALUES (999, '測試', 'test999', 'pwd', 999)");
        
        // 應該失敗因為 role_id 999 不存在
        $this->assertFalse($result, '外鍵約束未正確實施 - 應拒絕無效的 role_id');
        
        // 清理測試數據
        $this->mysqli->query("DELETE FROM accounts WHERE u_id = 999");
    }

    /**
     * 測試：驗證外鍵約束 - 無效的 r_id
     */
    public function testForeignKeyConstraintRestaurantId()
    {
        // 嘗試插入無效的 r_id
        $result = $this->mysqli->query("INSERT INTO accounts (u_id, name, accounts, password, role_id, r_id) 
                                      VALUES (998, '測試', 'test998', 'pwd', 2, 999)");
        
        // 應該失敗因為 r_id 999 不存在
        $this->assertFalse($result, '外鍵約束未正確實施 - 應拒絕無效的 r_id');
        
        // 清理測試數據
        $this->mysqli->query("DELETE FROM accounts WHERE u_id = 998");
    }

    /**
     * 測試：驗證唯一約束 - accounts 字段必須唯一
     */
    public function testUniqueConstraintAccounts()
    {
        // 嘗試插入相同的 accounts 值
        $result = $this->mysqli->query("INSERT INTO accounts (u_id, name, accounts, password, role_id) 
                                      VALUES (997, '測試', 'admin', 'pwd', 1)");
        
        // 應該失敗因為 'admin' 已存在
        $this->assertFalse($result, '唯一約束未正確實施 - accounts 字段應唯一');
        
        // 清理測試數據
        $this->mysqli->query("DELETE FROM accounts WHERE u_id = 997");
    }

    /**
     * 測試：created_at 字段的預設值
     */
    public function testCreatedAtDefaultValue()
    {
        // 插入新記錄不指定 created_at
        $this->mysqli->query("INSERT INTO accounts (u_id, name, accounts, password, role_id) 
                            VALUES (996, '測試', 'test996', 'pwd', 1)");
        
        $result = $this->mysqli->query("SELECT created_at FROM accounts WHERE u_id = 996");
        $row = $result->fetch_assoc();
        
        // created_at 應該被自動設置
        $this->assertNotNull($row['created_at'], 'created_at 預設值未正確設置');
        
        // 清理測試數據
        $this->mysqli->query("DELETE FROM accounts WHERE u_id = 996");
    }

    /**
     * 測試：資料庫字符集
     */
    public function testDatabaseCharset()
    {
        $result = $this->mysqli->query("SELECT @@character_set_database");
        $row = $result->fetch_row();
        
        // 驗證使用 utf8mb4 字符集以支持中文
        $this->assertStringContainsString('utf8', $row[0], '資料庫未使用 UTF-8 字符集');
    }

    /**
     * 測試：驗證所有表的引擎為 InnoDB
     */
    public function testTablesUseInnoDBEngine()
    {
        $result = $this->mysqli->query("SELECT TABLE_NAME, ENGINE FROM INFORMATION_SCHEMA.TABLES 
                                       WHERE TABLE_SCHEMA = '" . $this->db_name . "' AND TABLE_NAME IN ('accounts', 'permissions', 'restaurants')");
        
        while ($row = $result->fetch_assoc()) {
            $this->assertEquals('InnoDB', $row['ENGINE'], $row['TABLE_NAME'] . ' 表未使用 InnoDB 引擎');
        }
    }
}
