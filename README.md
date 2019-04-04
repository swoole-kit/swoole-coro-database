# Swoole Coro Database

基于 [`PHP-MySQLi-Database-Class`](https://github.com/ThingEngineer/PHP-MySQLi-Database-Class) 进行适配，支持Swoole协程模式的数据库DAO访问层

## 支持作者

本类库的诞生离不开原作者的辛勤努力，您可以向类库作者进行捐赠

```
This software is developed during my free time and I will be glad if somebody will support me.
这个软件是在我空闲时开发的，如果有人支持我，我会很高兴的。

Everyone's time should be valuable, so please consider donating.
每个人的时间都是宝贵的，所以请考虑捐赠。

```

[通过 PAYPAL 向原作者捐赠](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=a%2ebutenka%40gmail%2ecom&lc=DO&item_name=mysqlidb&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted)

### 如何安装

由于对类库进行了协程化改造和命名空间支持，以及大量使用了新特性，您需要在 [PHP7.2+](https://www.php.net/) / [Swoole4.3+](https://www.swoole.com/) 环境下使用，并且以 [Composer](https://getcomposer.org/) 作为类库加载器，然后创建协程，即可在协程中愉快地使用Mysql数据库！

> 由于类库仍处于开发阶段，请引入 `dev-master` 代码包

```
composer require swoole-kit/swoole-coro-database:dev-master
```

### 初始化对象

由于配置项多而复杂，故将配置项进行了对象化，一个简单的初始化栗子(默认utf8编码)

> 数据库配置类 : `SwooleKit\CoroDatabase\CoroMysqlConfig`

```php

// 创建一个数据库配置类
$coroMysqlConfig = new \SwooleKit\CoroDatabase\CoroMysqlConfig;

// 数据库信息设置(如果你的配置和默认值一样也可以不设置)
$coroMysqlConfig->setHostname('127.0.0.1');  // 数据库地址 默认为 '127.0.0.1'
$coroMysqlConfig->setHostport(3306);         // 数据库端口 默认为 3306 (字符串也可以接受)
$coroMysqlConfig->setUsername('root');       // 数据库用户 默认为 root
$coroMysqlConfig->setPassword('');           // 数据库密码 默认为 空字符串
$coroMysqlConfig->setDatabase('');           // 数据库名称 默认为 空字符串
$coroMysqlConfig->setCharset('utf8');        // 数据库编码 默认为 utf8
$coroMysqlConfig->setPrefix('');             // 表名称前缀 默认为 空字符串

// 数据库配置设置(如果不知道这些选项是干嘛的请维持默认值)
$coroMysqlConfig->setConnectTimeOut(-1);  // 连接超时 (客户端连接到服务端的超时时间 -1或0为永不超时)
$coroMysqlConfig->setExecuteTimeOut(-1);  // 查询超时 (执行查询语句或预处理超时时间 -1或0为永不超时)
$coroMysqlConfig->setConnectMaxRetryTimes(1);  // 连接失败重连次数 ( 默认1次 )

// 创建一个数据库操作类
$coroMysql = new \SwooleKit\CoroDatabase\CoroMysql($coroMysqlConfig);

```

由于Swoole的生命周期原因，故取消了原有的单例模式，需要使用单例请自行注意生命周期和协程安全问题！

> 类库使用了非常严格的抛出异常，任何查询失败，参数错误等都将抛出异常，请注意自行捕获进行处理

### 多数据库链接

由于协程/异步模式下共用链接是不安全的，需要多数据库连接请使用连接池模式，创建多个池来管理多个库的连接，按需取用！

### 对象映射

当前版本已移除，下一版本计划加入模型功能

### 插入数据

本栗子和下面的栗子都假设已正确连接到Mysql并可以执行操作，向某个表插入数据只需要这样操作

```php

$data = Array("login" => "admin", "firstName" => "John", "lastName" => 'Doe');

$lastInsertId = $coroMysql->table('user')->insert($data);  // 表名称预指定
$lastInsertId = $coroMysql->insert($data,'user');          // 插入指定表名

if ($lastInsertId) echo "insert success id = {$lastInsertId}";

```

> 这里和原类库有不同的地方是可以预先指定表名称，以方便习惯TP的用户，可以类似TP5的风格进行操作，在insert方法指定的表名称，优先级最高，会覆盖table方法的设置，其他方法(Update/Delete)也是一样，下面的例子中如果最终调用没有带表名称，则默认为已经使用table设置了操作表名称

还可以非常自由地使用各种数据库自带的函数，关于各种函数的使用方法将会在后面章节详细介绍

```php
$data = Array(
    'password'  => $coroMysql->func('SHA1(?)', Array("secretpassword+salt")),
    'createdAt' => $coroMysql->now(),
    'expires'   => $coroMysql->now('+1Y')
);

$lastInsertId = $coroMysql->insert($data,'user');
```

也可以执行 on duplicate key update 存在则更新，不存在则插入

```php
$data = Array("login"     => "admin",
              "firstName" => "John",
              "lastName"  => 'Doe',
              "createdAt" => $coroMysql->now(),
              "updatedAt" => $coroMysql->now(),
);
$updateColumns = Array("updatedAt");
$lastInsertId = "id";
$coroMysql->onDuplicate($updateColumns, $lastInsertId); // 存在则更新updateColumns多个字段 返回lastInsertId字段的值
$id = $coroMysql->insert($data, 'admin');
```

> 注意: 如果没有行被插入 则不会返回插入id 可以使用 `$coroMysql->getAffectRows()` 获取影响的行数 至于为什么修改一行数据AffectRows会等于2 请自行百度

也可以进行 Replace into 的插入操作，方法名称为replace，操作和上面栗子一致就不再赘述

> 批量插入已经移除，下个版本将更换实现

### 更新数据

```php
$data = Array(
    'firstName' => 'Bobby',
    'lastName'  => 'Tables',
    'editCount' => $coroMysql->inc(2),  // 自增
    'editCount' => $coroMysql->dec(2),  // 自减
    'active'    => $coroMysql->not()    // 取反
);
$coroMysql->where('id', 1)->update($data, 'table');
```

由于无条件更新的危险性，当没有设置查询条件时，更新将抛出异常，此时需要将第三个参数$force设置为true，允许进行无条件更新，设置第四个参数$limit可以限制被更新的记录数

### 查询操作

贴合TP5玩家的习惯，原类库的get/getOne方法分别对应改为select/find方法，操作更流畅

```php

// 正常玩法
$adminUsers = $coroMysql->table('admin')->select(); // 获取所有行
$adminUsers = $coroMysql->select('admin');          // 等效的写法

// 返回数量限制
$adminUsers = $coroMysql->limit(10)->select('admin');  // SELECT .. LIMIT 10
$adminUsers = $coroMysql->select('admin',10);          // 等效的写法

// 获取指定的列
$adminUsers = $coroMysql->columns('id')->select('id,username');     // SELECT id,username ...
$adminUsers = $coroMysql->select('admin',null,'id,username');       // 等效的写法
$adminUsers = $coroMysql->select('admin',null,['id','username']);   // 等效的写法

// 指定列的别名
$adminUsers = $coroMysql->select('admin',null,'id,username as name');     // SELECT id,username as name ...
$adminUsers = $coroMysql->select('admin',null,['id','username as name']); // 等效的写法

// 获取一行数据 (AUTO LIMIT 1)
$adminUsers = $coroMysql->table('admin')->find(); // 只获取一行
$adminUser = $coroMysql->find('admin');           // 等效的写法

// 别名聚合查询
$adminUsers = $coroMysql->find('admin','count(*) as cnt');  // SELECT count(*) as cnt ... LIMIT 1

// 获取列和字段的语法糖
$value = $coroMysql->value('username', 'admin'); // SELECT username FROM admin LIMIT 1 且 取出值
$value = $coroMysql->column('username', 'admin'); // SELECT username FROM admin 且 将username字段取出为一维数组

```

### 分页查询

> 鉴于原有分页查询比较简陋，本版本暂时移除，下一版本进行重构

### 返回值转换

> 感觉很鸡肋的功能，已移除，后续可能加入 查询结果集对象 抽象查询结果，对该功能进行重构

### 直接执行语句

支持进行参数绑定，建议始终使用参数绑定来保证语句的安全性，鉴于某些语句可能需要较长的执行时间，支持向第三个参数传入浮点数(秒)，为该条语句单独约定查询超时

```php
$users = $coroMysql->rawQuery('SELECT * from users where id >= ?', Array (10));
```

> 文档完善中 不尽之处 请查阅源码

### Where / Having Methods
`where()`, `orWhere()`, `having()` and `orHaving()` methods allows you to specify where and having conditions of the query. All conditions supported by where() are supported by having() as well.

WARNING: In order to use column to column comparisons only raw where conditions should be used as column name or functions cant be passed as a bind variable.

Regular == operator with variables:
```php
$db->where ('id', 1);
$db->where ('login', 'admin');
$results = $db->get ('users');
// Gives: SELECT * FROM users WHERE id=1 AND login='admin';
```

```php
$db->where ('id', 1);
$db->having ('login', 'admin');
$results = $db->get ('users');
// Gives: SELECT * FROM users WHERE id=1 HAVING login='admin';
```


Regular == operator with column to column comparison:
```php
// WRONG
$db->where ('lastLogin', 'createdAt');
// CORRECT
$db->where ('lastLogin = createdAt');
$results = $db->get ('users');
// Gives: SELECT * FROM users WHERE lastLogin = createdAt;
```

```php
$db->where ('id', 50, ">=");
// or $db->where ('id', Array ('>=' => 50));
$results = $db->get ('users');
// Gives: SELECT * FROM users WHERE id >= 50;
```

BETWEEN / NOT BETWEEN:
```php
$db->where('id', Array (4, 20), 'BETWEEN');
// or $db->where ('id', Array ('BETWEEN' => Array(4, 20)));

$results = $db->get('users');
// Gives: SELECT * FROM users WHERE id BETWEEN 4 AND 20
```

IN / NOT IN:
```php
$db->where('id', Array(1, 5, 27, -1, 'd'), 'IN');
// or $db->where('id', Array( 'IN' => Array(1, 5, 27, -1, 'd') ) );

$results = $db->get('users');
// Gives: SELECT * FROM users WHERE id IN (1, 5, 27, -1, 'd');
```

OR CASE:
```php
$db->where ('firstName', 'John');
$db->orWhere ('firstName', 'Peter');
$results = $db->get ('users');
// Gives: SELECT * FROM users WHERE firstName='John' OR firstName='peter'
```

NULL comparison:
```php
$db->where ("lastName", NULL, 'IS NOT');
$results = $db->get("users");
// Gives: SELECT * FROM users where lastName IS NOT NULL
```

LIKE comparison:
```php
$db->where ("fullName", 'John%', 'like');
$results = $db->get("users");
// Gives: SELECT * FROM users where fullName like 'John%'
```

Also you can use raw where conditions:
```php
$db->where ("id != companyId");
$db->where ("DATE(createdAt) = DATE(lastLogin)");
$results = $db->get("users");
```

Or raw condition with variables:
```php
$db->where ("(id = ? or id = ?)", Array(6,2));
$db->where ("login","mike")
$res = $db->get ("users");
// Gives: SELECT * FROM users WHERE (id = 6 or id = 2) and login='mike';
```


Find the total number of rows matched. Simple pagination example:
```php
$offset = 10;
$count = 15;
$users = $db->withTotalCount()->get('users', Array ($offset, $count));
echo "Showing {$count} from {$db->totalCount}";
```

### Query Keywords
To add LOW PRIORITY | DELAYED | HIGH PRIORITY | IGNORE and the rest of the mysql keywords to INSERT (), REPLACE (), GET (), UPDATE (), DELETE() method or FOR UPDATE | LOCK IN SHARE MODE into SELECT ():
```php
$db->setQueryOption ('LOW_PRIORITY')->insert ($table, $param);
// GIVES: INSERT LOW_PRIORITY INTO table ...
```
```php
$db->setQueryOption ('FOR UPDATE')->get ('users');
// GIVES: SELECT * FROM USERS FOR UPDATE;
```

Also you can use an array of keywords:
```php
$db->setQueryOption (Array('LOW_PRIORITY', 'IGNORE'))->insert ($table,$param);
// GIVES: INSERT LOW_PRIORITY IGNORE INTO table ...
```

Same way keywords could be used in SELECT queries as well:
```php
$db->setQueryOption ('SQL_NO_CACHE');
$db->get("users");
// GIVES: SELECT SQL_NO_CACHE * FROM USERS;
```

Optionally you can use method chaining to call where multiple times without referencing your object over and over:

```php
$results = $db
	->where('id', 1)
	->where('login', 'admin')
	->get('users');
```

### Delete Query
```php
$db->where('id', 1);
if($db->delete('users')) echo 'successfully deleted';
```


### Ordering method
```php
$db->orderBy("id","asc");
$db->orderBy("login","Desc");
$db->orderBy("RAND ()");
$results = $db->get('users');
// Gives: SELECT * FROM users ORDER BY id ASC,login DESC, RAND ();
```

Order by values example:
```php
$db->orderBy('userGroup', 'ASC', array('superuser', 'admin', 'users'));
$db->get('users');
// Gives: SELECT * FROM users ORDER BY FIELD (userGroup, 'superuser', 'admin', 'users') ASC;
```

If you are using setPrefix () functionality and need to use table names in orderBy() method make sure that table names are escaped with ``.

```php
$db->setPrefix ("t_");
$db->orderBy ("users.id","asc");
$results = $db->get ('users');
// WRONG: That will give: SELECT * FROM t_users ORDER BY users.id ASC;

$db->setPrefix ("t_");
$db->orderBy ("`users`.id", "asc");
$results = $db->get ('users');
// CORRECT: That will give: SELECT * FROM t_users ORDER BY t_users.id ASC;
```

### Grouping method
```php
$db->groupBy ("name");
$results = $db->get ('users');
// Gives: SELECT * FROM users GROUP BY name;
```

Join table products with table users with LEFT JOIN by tenantID
### JOIN method
```php
$db->join("users u", "p.tenantID=u.tenantID", "LEFT");
$db->where("u.id", 6);
$products = $db->get ("products p", null, "u.name, p.productName");
print_r ($products);
```

### Join Conditions
Add AND condition to join statement
```php
$db->join("users u", "p.tenantID=u.tenantID", "LEFT");
$db->joinWhere("users u", "u.tenantID", 5);
$products = $db->get ("products p", null, "u.name, p.productName");
print_r ($products);
// Gives: SELECT  u.login, p.productName FROM products p LEFT JOIN users u ON (p.tenantID=u.tenantID AND u.tenantID = 5)
```
Add OR condition to join statement
```php
$db->join("users u", "p.tenantID=u.tenantID", "LEFT");
$db->joinOrWhere("users u", "u.tenantID", 5);
$products = $db->get ("products p", null, "u.name, p.productName");
print_r ($products);
// Gives: SELECT  u.login, p.productName FROM products p LEFT JOIN users u ON (p.tenantID=u.tenantID OR u.tenantID = 5)
```

### Properties sharing
It is also possible to copy properties

```php
$db->where ("agentId", 10);
$db->where ("active", true);

$customers = $db->copy ();
$res = $customers->get ("customers", Array (10, 10));
// SELECT * FROM customers where agentId = 10 and active = 1 limit 10, 10

$cnt = $db->getValue ("customers", "count(id)");
echo "total records found: " . $cnt;
// SELECT count(id) FROM users where agentId = 10 and active = 1
```

### Subqueries
Subquery init

Subquery init without an alias to use in inserts/updates/where Eg. (select * from users)
```php
$sq = $db->subQuery();
$sq->get ("users");
```
 
A subquery with an alias specified to use in JOINs . Eg. (select * from users) sq
```php
$sq = $db->subQuery("sq");
$sq->get ("users");
```

Subquery in selects:
```php
$ids = $db->subQuery ();
$ids->where ("qty", 2, ">");
$ids->get ("products", null, "userId");

$db->where ("id", $ids, 'in');
$res = $db->get ("users");
// Gives SELECT * FROM users WHERE id IN (SELECT userId FROM products WHERE qty > 2)
```

Subquery in inserts:
```php
$userIdQ = $db->subQuery ();
$userIdQ->where ("id", 6);
$userIdQ->getOne ("users", "name"),

$data = Array (
    "productName" => "test product",
    "userId" => $userIdQ,
    "lastUpdated" => $db->now()
);
$id = $db->insert ("products", $data);
// Gives INSERT INTO PRODUCTS (productName, userId, lastUpdated) values ("test product", (SELECT name FROM users WHERE id = 6), NOW());
```

Subquery in joins:
```php
$usersQ = $db->subQuery ("u");
$usersQ->where ("active", 1);
$usersQ->get ("users");

$db->join($usersQ, "p.userId=u.id", "LEFT");
$products = $db->get ("products p", null, "u.login, p.productName");
print_r ($products);
// SELECT u.login, p.productName FROM products p LEFT JOIN (SELECT * FROM t_users WHERE active = 1) u on p.userId=u.id;
```

### EXISTS / NOT EXISTS condition
```php
$sub = $db->subQuery();
    $sub->where("company", 'testCompany');
    $sub->get ("users", null, 'userId');
$db->where (null, $sub, 'exists');
$products = $db->get ("products");
// Gives SELECT * FROM products WHERE EXISTS (select userId from users where company='testCompany')
```

### Has method
A convenient function that returns TRUE if exists at least an element that satisfy the where condition specified calling the "where" method before this one.
```php
$db->where("user", $user);
$db->where("password", md5($password));
if($db->has("users")) {
    return "You are logged";
} else {
    return "Wrong user/password";
}
``` 
### Helper methods
Disconnect from the database:
```php
    $db->disconnect();
```

Reconnect in case mysql connection died:
```php
if (!$db->ping())
    $db->connect()
```

Get last executed SQL query:
Please note that function returns SQL query only for debugging purposes as its execution most likely will fail due missing quotes around char variables.
```php
    $db->get('users');
    echo "Last executed query was ". $db->getLastQuery();
```

Check if table exists:
```php
    if ($db->tableExists ('users'))
        echo "hooray";
```

mysqli_real_escape_string() wrapper:
```php
    $escaped = $db->escape ("' and 1=1");
```

### Transaction helpers
Please keep in mind that transactions are working on innoDB tables.
Rollback transaction if insert fails:
```php
$db->startTransaction();
...
if (!$db->insert ('myTable', $insertData)) {
    //Error while saving, cancel new record
    $db->rollback();
} else {
    //OK
    $db->commit();
}
```


### Error helpers
After you executed a query you have options to check if there was an error. You can get the MySQL error string or the error code for the last executed query. 
```php
$db->where('login', 'admin')->update('users', ['firstName' => 'Jack']);

if ($db->getLastErrno() === 0)
    echo 'Update succesfull';
else
    echo 'Update failed. Error: '. $db->getLastError();
```

### Query execution time benchmarking
To track query execution time setTrace() function should be called.
```php
$db->setTrace (true);
// As a second parameter it is possible to define prefix of the path which should be striped from filename
// $db->setTrace (true, $_SERVER['SERVER_ROOT']);
$db->get("users");
$db->get("test");
print_r ($db->trace);
```

```
    [0] => Array
        (
            [0] => SELECT  * FROM t_users ORDER BY `id` ASC
            [1] => 0.0010669231414795
            [2] => MysqliDb->get() >>  file "/avb/work/PHP-MySQLi-Database-Class/tests.php" line #151
        )

    [1] => Array
        (
            [0] => SELECT  * FROM t_test
            [1] => 0.00069189071655273
            [2] => MysqliDb->get() >>  file "/avb/work/PHP-MySQLi-Database-Class/tests.php" line #152
        )

```

### Table Locking
To lock tables, you can use the **lock** method together with **setLockMethod**. 
The following example will lock the table **users** for **write** access.
```php
$db->setLockMethod("WRITE")->lock("users");
```

Calling another **->lock()** will remove the first lock.
You can also use
```php
$db->unlock();
```
to unlock the previous locked tables.
To lock multiple tables, you can use an array.
Example:
```php
$db->setLockMethod("READ")->lock(array("users", "log"));
```
This will lock the tables **users** and **log** for **READ** access only.
Make sure you use **unlock()* afterwards or your tables will remain locked!
