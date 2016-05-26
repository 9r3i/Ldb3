#Ldb3 API (Application Programming Interface)

Alhamdulillah, this is the 3rd time I created a database class,
but this one is the latest that I also want to write the APIs,

Before I give the APIs, here is the scheme of the Ldb3.
```php
/*** Kerangka dasar output Ldb3 dalam bentuk array ***/
$kerangka_Ldb3 = array('_db_name'=>array(
  'access'=>array('db_username'=>'db_password'),
  'db_content'=>array(
    'table_name'=>array(
      'table_option'=>array(
        'aid'=>0,
        'column_name'=>array('','',''),
        'column_default'=>array(
          'column_name'=>'',
        ),
      ),
      'table_content'=>array(
        'column_name'=>array(
          'tid'=>'',
        ),
      ),
    ),
  )
));
```

--------------------------------------------------
In this section, I'll show you how it works and how to use the Ldb3


#Call the class of Ldb
```php
$ldb = new Ldb3();
```
Output: Ldb3Class object


#Customize database directory name or Create a new database directory
```php
$ldb = new Ldb3($dir_name);
```
Output: Ldb3Class object

Example:
```php
$dir = '_database';
$ldb = new Ldb3($dir);
```


#Create a new database
```php
$create_db = $ldb->create_database($db_name,$db_username,$db_password);
```
Output: Boolean (true/false)

Example:
```php
$ldb = new Ldb3();
$create_db = $ldb->create_database($db_name,$db_username,$db_password);
```


#Connect into database
```php
$connect = $ldb->connect($db_name,$db_username,$db_password);
```
Output: Ldb3Class object (Status: connected to pointed-database)

Example:
```php
$ldb = new Ldb3();
$connect = $ldb->connect($db_name,$db_username,$db_password);
```


#Get access string
```php
$ldb->access;
```
Output: string (access status)


#Get the last error
```php
$ldb->error;
```
Output: string (last error)


#Get all errors
```php
$ldb->errors;
```
Output: array of errors


#Get the connected database name
```php
$connect->database;
```
Outout: string (connected database name)


#Show all created database file of Ldb in database directory
```php
$connect->show_database();
```
Output: array


#Create a new table
```php
$connect->create_table($table_name,$columns=array());
```
Output: Boolean (true/false)

Example:
```php
$ldb = new Ldb3();
$connect = $ldb->connect($db_name,$db_username,$db_password);
$connect->create_table(
  $table_name,
  array(
    $column_name,
    $column_name=>DEFAULT_VALUE,
    $column_name,
    // ... etc.
  )
);
```


#Valid columns/fields default value

- AID (Auto Increasement Data) -> integer
- BID (Base ID) -> integer -> special option
- CID (Cross ID) -> hexadecimal -> special option
- TID (Time ID) -> number -> float 9 microtime
- TIME (Current Server Time) -> integer -> base on time()
- DATE (Current Server Date) -> Format: date('Y-m-d')
- TIMESTAMP (Current Server Timestamp) -> Format: date('Y-m-d H:i:s')
- NULL (Nullify) -> null


#Show all tables inside the connected database
```php
$connect->show_tables();
```
Output: array (tables of connected database)

Example:
```php
$ldb = new Ldb3();
$connect = $ldb->connect($db_name,$db_username,$db_password);
print_r($connect->show_tables());
```


#Alter a table
```php
$connect->alter_table($table_name,$columns=array());
```
Output: Boolean (true/false)

Example:
```php
$ldb = new Ldb3();
$connect = $ldb->connect($db_name,$db_username,$db_password);
$connect->alter_table(
  $table_name,
  array(
    'id'=>'AID',
    'title',
    'content'=>'NULL',
    'date'=>'DATE'
  )
);
```


#Drop a table
```php
$connect->drop_table($table_name);
```
Output: Boolean (true/false)

Example:
```php
$ldb = new Ldb3();
$connect = $ldb->connect($db_name,$db_username,$db_password);
$connect->drop_table($table_name);
```


#Show all columns/fields in the table
```php
$connect->show_columns($table_name);
```
Output: array

Example:
```php
$ldb = new Ldb3();
$connect = $ldb->connect($db_name,$db_username,$db_password);
print_r($connect->show_columns($table_name));
```


#Add/Create a new user of the connected database
```php
$connect->create_user($username,$password);
```
Output: Boolean (true/false)

Example:
```php
$ldb = new Ldb3();
$connect = $ldb->connect($db_name,$db_username,$db_password);
$connect->create_user($username,$password);
```


#Delete a user
```php
$connect->delete_user($username);
```
Output: Boolean (true/false)

Example:
```php
$ldb = new Ldb3();
$connect = $ldb->connect($db_name,$db_username,$db_password);
$connect->delete_user($username);
```


#Insert data
```php
$connect->insert($table_name,$data=array());
```
Output: Boolean (true/false)

Example:
```php
$ldb = new Ldb3();
$connect = $ldb->connect($db_name,$db_username,$db_password);
$data = array(
  'title'=>'Title Post',
  'content'=>'Content post',
);
$connect->insert($table_name,$data);
```


#Delete data
```php
$connect->delete($table_name,$where);
```
Output: Boolean (true/false)

Example:
```php
$ldb = new Ldb3();
$connect = $ldb->connect($db_name,$db_username,$db_password);
$where = 'id=1&title=Title Post';
$connect->delete($table_name,$where);
```


#Update data
*some different as Ldb2, Ldb3 using content data in second argumment
```php
$connect->update($table_name,$data=array(),$where);
```
```txt
$where = string url query
```
Output: Boolean (true/false)

Example:
```php
$ldb = new Ldb3();
$connect = $ldb->connect($db_name,$db_username,$db_password);
$data = array('title'=>'Post Title');
$connect->update($table_name,$data,'id=1');
```


#Select data
```php
$select = $ldb->select($table_name,$where,$option);
```
```txt
$where = string url query (default: null)
$option = string url query (default: null)
```
Options:
- key = output key (default: none)
- order = order output by (default: none)
- sort = arrange output: ASC/DESC (default: none)
- start = start position of the array output (default: 0)
- limit = limit of output (default: 25)

Output: array

Example:
```php
$ldb = new Ldb3();
$connect = $ldb->connect($db_name,$db_username,$db_password);
$select = $ldb->select($table_name,null,'key=id&order=date&sort=DESC&start=0&limit=10');
```



SELECTED DATA
-------------

- Get selected rows:
  ```php
  $select->rows;
  ```
  Output: integer

- Get table rows:
  ```php
  $select->table_rows;
  ```
  Output: integer

- Get process time:
  ```php
  $select->process_time;
  ```
  Output: float of time (float to 4)

- Get error:
  ```php
  $select->error;
  ```
  Output: string

- Get fetch store:
  ```php
  // get all data store of selected item
  $select->fetch_store();
  ```
  Output: array

- Get fetch array:
  ```php
  // get data of each row
  $select->fetch_array();
  ```
  Output: array


