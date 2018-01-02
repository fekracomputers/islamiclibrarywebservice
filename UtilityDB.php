<?php

define("PAGE_LIMIT", 10);

class UtilityDB {
    
    private static $mainDB = NULL;
    private static $usersDB = NULL;
    private static $ftsDB = NULL;
    private static $bookDB = NULL;
    private static $bookID = 0;

    /**
     * @brief generate limit seaction of SQL statment
     */
    private static function genSQLLimit($start = -1, $limit = -1)
    {
        $SQLLimit = "";
        if($start>=0 && $limit>=0){
            $SQLLimit = "LIMIT $limit OFFSET $start";
        }
        return $SQLLimit;
    }

    /**
     * @brief generate limit section of SQL statment
     */
    public static function buileSQLLike($fieldName, $keywords)
    {
        if($keywords==null)return "";
        $keywords = getSearchText("".$keywords);
        if(mb_strlen($keywords)==0)return "";
        $keywords = replace($keywords, " ", "%");
        return "AND $fieldName LIKE('%$keywords%')";
    }
    
    /**
     * @brief generate limit section of SQL statment
     */
    public static function buileFTSSQLMatch($fieldName, $keywords)
    {
        if($keywords==null)return "";
        $keywords = getSearchText("".$keywords);
        if(mb_strlen($keywords)==0)return "";
        return "AND $fieldName MATCH('$keywords')";
    }

    /**
     * @brief get main db object
     */
    private static function getMainDB(){
        global $baseDataFolder;
        
        if(UtilityDB::$mainDB!==NULL){
            return UtilityDB::$mainDB;
        }
        
        try {
            UtilityDB::$mainDB = NULL;
            $dbFilePath = $baseDataFolder."/main.sqlite";
            UtilityDB::$mainDB = new SQLite3($dbFilePath);
        } catch(Exception $e) {
            die("Cannot open main database");
        }
        
        return UtilityDB::$mainDB;
    }
    
    /**
     * @brief get users db object
     */
    private static function getUsersDB() {
        global $baseLocalDataFolder;
        
        if(UtilityDB::$usersDB!==NULL){
            return UtilityDB::$usersDB;
        }
        try
        {
            UtilityDB::$usersDB = NULL;
            $dbFilePath = $baseLocalDataFolder."/Users.sqlite";
            UtilityDB::$usersDB = new SQLite3($dbFilePath);
            
            if(UtilityDB::dbSQLVal(UtilityDB::$usersDB, "SELECT email, preferences FROM userspreferences LIMIT 1;")===false)
            {
                echo '<p>Drop userpreferences';
                
                UtilityDB::$usersDB->query("DROP TABLE userspreferences;");

                $results = UtilityDB::$usersDB->query("CREATE TABLE userspreferences (email TEXT NOT NULL, preferences text DEFAULTNULL, PRIMARY KEY (email));");                
            }
        }
        catch(Exception $e){
            die("Cannot open main database");
        }
        return UtilityDB::$usersDB;
    }
    
    /**
     * @brief get fts db object
     */
    private static function getFTSDB(){
        global $baseDataFolder;
        
        if(UtilityDB::$ftsDB!==NULL){
            return UtilityDB::$ftsDB;
        }
        
        try {
            UtilityDB::$ftsDB = NULL;
            $dbFilePath = $baseDataFolder."/fts.sqlite";
            if(!file_exists($dbFilePath))return false;
            UtilityDB::$ftsDB = new SQLite3($dbFilePath);
        } catch(Exception $e) {
            die("Cannot open main database");
        }
        
        return UtilityDB::$ftsDB;
    }
    
    /**
     * @brief get main db object
     */
    private static function getBookDB($bookID){
        global $baseDataFolder;
        
        if(UtilityDB::$bookID==$bookID){
            return UtilityDB::$bookDB;
        }
        try{
            UtilityDB::$bookDB = NULL;
            $dbFilePath = $baseDataFolder."/books/$bookID.sqlite";
            if(!file_exists($dbFilePath))return false;
            UtilityDB::$bookDB = new SQLite3($dbFilePath);
        }
        catch(Exception $e){
            print_r($e);
            die("Cannot open book database");
        }
        UtilityDB::$bookID = $bookID;
        return UtilityDB::$bookDB;
    }
    
    /**
     * @brief get sql query single value
     */
    private static function dbSQLVal($db, $sql, $echo = false)
    {
        if($echo)echo $sql;
        
        $results = $db->query($sql);
        if($results===false){
            return false;
        }
        
        $row = $results->fetchArray();
        if($row===false){
            return false;
        }
        
        return $row[0];
    }
    
    /**
     * @brief get sql query row
     */
    private static function dbSQLRow($db, $sql, $echo = false)
    {
        if($echo)echo $sql;
        
        $results = $db->query($sql);
        if($results===false){
            return false;
        }
        
        $row = $results->fetchArray(SQLITE3_ASSOC);
        if($row===false){
            return false;
        }
        
        return $row;
    }
    
    /**
     * @brief get sql query row
     */
    private static function dbSQLRows($db, $sql, $echo = false)
    {
        if($echo)echo $sql;
        
        $results = $db->query($sql);
        if($results===false){
            return false;
        }
        
        $all = array();
        while($row = $results->fetchArray(SQLITE3_ASSOC)){
            $all[] = (object)$row;
        }
        
        return $all;
    }

    /**
     * @brief get sql query associative values
     */
    private static function dbSQLAssociative ($db, $sql, $echo = false)
    {
        if($echo)echo $sql;
        
        $results = $db->query($sql);
        if($results===false){
            return false;
        }
        
        $all = array();
        while($row = $results->fetchArray(SQLITE3_NUM)){
            $all[$row[0]] = $row[1];
        }
        
        return $all;
    }
    
    /**
     * @brief get sql query array values
     */
    private static function dbSQLArray ($db, $sql, $echo = false)
    {
        if($echo)echo $sql;
        
        $results = $db->query($sql);
        if($results===false){
            return false;
        }
        
        $all = array();
        while($row = $results->fetchArray(SQLITE3_NUM)){
            $all[] = $row[0];
        }
        
        return $all;
    }

    /**
     * @brief get book information value
     */
    private static function dbBookInfo($db, $name)
    {
        return UtilityDB::dbSQLVal($db, "SELECT value FROM info WHERE name='$name'");
    }

    /**
     * @brief generate main database
     */
    public static function generateMain()
    {
        $mainDB = UtilityDB::getMainDB();
        
        $mainDB->exec("DROP TABLE userspreferences;");
        $mainDB->exec("CREATE TABLE userspreferences (email TEXT NOT NULL, preferences text DEFAULTNULL, PRIMARY KEY (email));");                
        
        $mainDB->exec("DROP TABLE books;");
        $mainDB->exec("CREATE TABLE books (id INTEGER NOT NULL PRIMARY KEY, title TEXT, information TEXT, card TEXT, adddate DATETIME, accesscount INTEGER);");

        $mainDB->exec("DROP TABLE categories;");
        $mainDB->exec("CREATE TABLE categories (id INTEGER NOT NULL PRIMARY KEY, parentid INTEGER NOT NULL, title TEXT);");

        $mainDB->exec("DROP TABLE bookscategories;");
        $mainDB->exec("CREATE TABLE bookscategories (bookid INTEGER NOT NULL, categoryid INTEGER NOT NULL, PRIMARY KEY(bookid, categoryid));");

        $mainDB->exec("DROP TABLE authors;");
        $mainDB->exec("CREATE TABLE authors (id INTEGER NOT NULL PRIMARY KEY, name TEXT, information TEXT, birthhigriyear INTEGER NOT NULL, deathhigriyear INTEGER NOT NULL);");

        $mainDB->exec("DROP TABLE booksauthors;");
        $mainDB->exec("CREATE TABLE booksauthors (bookid INTEGER NOT NULL, authorid INTEGER NOT NULL, PRIMARY KEY(bookid, authorid));");            
        
        UtilityDB::$mainDB->close();
        UtilityDB::$mainDB = null;

        $filesPaths = getFiles("./data/books", ".sqlite");

        foreach ($filesPaths as $filePath)
        {
            UtilityDB::addBook($filePath);
        }
    }
    
    /**
     * @brief generate main database
     */
    public static function syncMain()
    {
        $mainDB  = UtilityDB::getMainDB();

        $filesPaths = getFiles("./data/books", ".sqlite");
            
        foreach ($filesPaths as $filePath)
        {
            $bookID = basename($filePath, ".sqlite");
                        
            $result = UtilityDB::dbSQLVal($mainDB, "SELECT id FROM books WHERE id = $bookID;");
            
            if($result===false)
            {
                UtilityDB::addBook($filePath, $mainDB);
            }
        }
    }
    
    /**
     * @brief add book
     */
    private static function addBook($filePath)
    {
        $mainDB = UtilityDB::getMainDB();
        
        $bookID = basename($filePath, ".sqlite");
        
        if(strlen($bookID)==0)return;

        $bookDB  = UtilityDB::getBookDB($bookID);
        
        $nPages = UtilityDB::dbSQLVal($bookDB, "SELECT count(*) from pages;");
        if($nPages==0 || $nPages===false)return;

        echo "<p> ----------------------------";
        echo "<p>".$filePath." - ".filesize($filePath);

        echo "<p>pages:". $nPages;

        $bookTitle = getSearchText(UtilityDB::dbBookInfo($bookDB,'booktitle'));
        $bookInformation = addslashes(UtilityDB::dbBookInfo($bookDB,'bookinformation'));
        $bookCard = addslashes(UtilityDB::dbBookInfo($bookDB,'bookcard'));
        
        //Add Book
        $statement = $mainDB->prepare("INSERT INTO books(id, title, information, card, adddate, accesscount) VALUES(:id, :title, :information, :card, CURRENT_TIMESTAMP, 0);");

        $statement->bindValue(':id', $bookID, SQLITE3_INTEGER);
        $statement->bindValue(':title', $bookTitle, SQLITE3_TEXT);
        $statement->bindValue(':information', $bookInformation, SQLITE3_TEXT);
        $statement->bindValue(':card', $bookCard, SQLITE3_TEXT);

        $result = $statement->execute();
        if($result===false)die($bookDB->lastErrorMsg());


        //Add Categories

        $categoryTitle = getSearchText(UtilityDB::dbBookInfo($bookDB,'category0title'));
        $categoryTitle = replace($categoryTitle, "مرقم اليا", "");
        $categoryTitle = getSearchText($categoryTitle);
        
        echo "<p>$categoryTitle - ".mb_strlen($categoryTitle)."<p>";

        $categoryID = UtilityDB::dbSQLVal($mainDB, "SELECT id from categories WHERE title = '$categoryTitle';");

        if($categoryID===false){
            
            $categoryID = UtilityDB::dbSQLVal($mainDB, "SELECT max(id) from categories;");
            
            if($categoryID===false)$categoryID = 0;

            $categoryID++;

            $mainDB->exec ("INSERT INTO categories(id, parentid, title) VALUES($categoryID, 0, '$categoryTitle');");
        }
        
        $mainDB->exec ("INSERT INTO bookscategories(bookid, categoryid) VALUES($bookID, $categoryID);");

        echo "<p>Category($categoryID):".$categoryTitle;

        //Add Authors

        $authorID = getSearchText(UtilityDB::dbBookInfo($bookDB,'author0id'));
        $authorName = getSearchText(UtilityDB::dbBookInfo($bookDB,'author0name'));
        $authorInformation = addslashes(UtilityDB::dbBookInfo($bookDB, 'author0information'));
        $authorBirthHigriYear = UtilityDB::dbBookInfo($bookDB,'author0birthhigriyear');;
        $authorDeathHigriYear = UtilityDB::dbBookInfo($bookDB,'author0deathhigriyear');

        $authorIDCount = UtilityDB::dbSQLVal($mainDB, "SELECT count(*) from authors WHERE id = $authorID;");

        if($authorIDCount==0){

            $mainDB->exec ("INSERT INTO authors(id, name, information, birthhigriyear, deathhigriyear) VALUES($authorID, '$authorName',  '$authorInformation', $authorBirthHigriYear, $authorDeathHigriYear);");
        }

        $mainDB->exec ("INSERT INTO booksauthors(bookid, authorid) VALUES($bookID,$authorID);");

        echo "<p>Author($authorID):".$authorName;

        //Finish
                
        echo "<p>Book($bookID):".$bookTitle;
    }

    /**
     * @brief generate index
     */
    private static function generateMetadata($bookID)
    {
        $time = echoTime(0);
        
        $bookDB  = UtilityDB::getBookDB($bookID);
        
        echo "<p> ----------------------------";
        echo "<p>Build titles for ($bookID) :".UtilityDB::dbBookInfo($bookDB,'booktitle');
        
        $mainDB  = UtilityDB::getMainDB();
        
        $bookDB->exec("DROP TABLE titles;");
        $bookDB->exec("CREATE TABLE titles (id INTEGER NOT NULL PRIMARY KEY, level INTEGER NOT NULL, partnumber INTEGER NOT NULL, pagenumber INTEGER NOT NULL, title TEXT);");
                
        $results = $bookDB->query("SELECT partnumber, pagenumber, page FROM pages ORDER BY partnumber, pagenumber;");
        if($results===false)die($bookDB->lastErrorMsg());
        
        $id = 0;
        
        while($row = $results->fetchArray(SQLITE3_ASSOC))
        {
            $partNumber = $row['partnumber'];
            $pageNumber = $row['pagenumber'];
            $page = $row['page'];
            
            $start = 0; 
            while(true)
            {
                $start = mb_stripos($page, "<h", $start);
                if($start===false)break;
                if($start>=0)
                {
                    $level = mb_substr($page, $start+2, 1);
                    if(mb_stripos("123456", $level, 0)===false)break;

                    $start = mb_stripos($page, ">", $start);
                    if($start===false)break;
                    $end = mb_stripos($page, "</h", $start);
                    if($end===false)break;
                    
                    $id++;
                    $title = addslashes(getSearchText(mb_substr($page, $start+1, $end-$start-1)));

                    $result = $bookDB->exec("INSERT INTO titles(id, level, partnumber, pagenumber, title) VALUES($id, $level, $partNumber, $pageNumber, '$title');");
                    if($result===false)die($bookDB->lastErrorMsg());
                }
            }
        }
              
        $mainDB->exec("UPDATE books SET status = 1 WHERE id = $bookID;");
        
        echoTime($time, "Done, $id title are generated in ");
    }
    
    /**
     * @brief get category id given category title
     */
    public static function getCategoryID($title)
    {
        return UtilityDB::dbSQLVal(UtilityDB::getMainDB(), "SELECT id FROM categories WHERE title = '$title';");
    }

    /**
     * @brief get book id given book title
     */
    public static function getBookID($title)
    {
        return UtilityDB::dbSQLVal(UtilityDB::getMainDB(), "SELECT id FROM books WHERE title = '$title';");
    }

    /**
     * @brief get author id given author name
     */
    public static function getAuthorID($name)
    {
        return UtilityDB::dbSQLVal(UtilityDB::getMainDB(), "SELECT id FROM authors WHERE name = '$name';");
    }

    /**
     * @brief get categories (id, title) with given parent id, start and limit (ignore start if nulll. ignore limit if null )
     */
    public static function getCategories($keywords = null, $parentCategoryID = null, $startAfterID = null, $limit = MAX_RESULT_COUNT) 
    {        
        $sqlTitleFilter = UtilityDB::buileSQLLike("title", $keywords);
        
        $sqlParentFilter = "";
        if($parentCategoryID!=null)$sqlParentFilter = "AND parentid = $parentCategoryID";
        
        $sqlStartAfter = "";
        if($startAfterID!=null)$sqlStartAfter = " AND title>(SELECT title FROM categories WHERE id = $startAfterID)";
        
        return UtilityDB::dbSQLAssociative(UtilityDB::getMainDB(), "SELECT id, title FROM categories WHERE 1 $sqlStartAfter $sqlParentFilter $sqlTitleFilter ORDER BY title LIMIT $limit;");
    }
        
    /**
     * @brief get authors (id, name) for given filter, start and limit (ignore start if nulll. ignore limit if null )
     */
    public static function getAuthors($keywords = null, $startAfterID = null, $limit = MAX_RESULT_COUNT) 
    {        
        $sqlNameFilter = UtilityDB::buileSQLLike("name", $keywords);
        
        $sqlStartAfter = "";
        if($startAfterID!=null)$sqlStartAfter = " AND name>(SELECT name FROM authors WHERE id = $startAfterID)";

        return UtilityDB::dbSQLAssociative(UtilityDB::getMainDB(), "SELECT id, name FROM authors WHERE 1 $sqlStartAfter $sqlNameFilter ORDER BY name LIMIT $limit;");
    }

    /**
     * @brief get books (id, title) for given filter, category id, author id, start and limit (ignore start if nulll. ignore limit if null )
     */
    public static function getBooks($keywords = null, $of = null, $ofData = null, $startAfterID = null, $limit = MAX_RESULT_COUNT) 
    {        
        $sqlTitleFilter = UtilityDB::buileSQLLike("title", $keywords);
        
        $sqlOf = "";
        if($of=="category")
        {
            if(intval($ofData)>0)
                $sqlOf = "AND id IN (SELECT bookid FROM bookscategories WHERE categoryid = $ofData)";
        }
        else if($of=="author")$sqlOf = "AND id IN (SELECT bookid FROM booksauthors WHERE authorid = $ofData)";
        else if($of=="books")$sqlOf = "AND id IN $ofData";
        else return false;
        
        $sqlStartAfter = "";
        if($startAfterID!=null)$sqlStartAfter = " AND title>(SELECT title FROM books WHERE id = $startAfterID)";
        
        return UtilityDB::dbSQLAssociative(UtilityDB::getMainDB(), "SELECT id, title FROM books WHERE 1 $sqlOf $sqlStartAfter $sqlParentFilter $sqlTitleFilter ORDER BY title LIMIT $limit;", false);
    }
    
    /**
     * @brief get categories (id, title) for given book id
     */
    public static function getBookCategories($bookID)
    {
        return UtilityDB::dbSQLAssociative(UtilityDB::getMainDB(), "SELECT id, title FROM categories WHERE id IN(SELECT categoryid FROM bookscategories WHERE bookid = $bookID) ORDER BY title;");
    }

    /**
     * @brief get authors (id, name) for given book id
     */
    public static function getBookAuthors($bookID)
    {
        return UtilityDB::dbSQLAssociative(UtilityDB::getMainDB(), "SELECT id, name FROM authors WHERE id IN(SELECT authorid FROM booksauthors WHERE bookid = $bookID) ORDER BY name;");
    }

    /**
     * @brief get category (id, title, ...) for given category id
     */
    public static function getCategoryInfo($categoryID)
    {
        return UtilityDB::dbSQLRow(UtilityDB::getMainDB(), "SELECT * FROM categories WHERE id = $categoryID;");
        
    }

    /**
     * @brief get author (id, name, information, birthhigriyear, deathhigriyear, ...) for given author id
     */
    public static function getAuthorInfo($authorID)
    {
        return UtilityDB::dbSQLRow(UtilityDB::getMainDB(), "SELECT * FROM authors WHERE id = $authorID;");
    }

    /**
     * @brief get book (id, title) for given book id
     */
    public static function accessBook($bookID)
    {
        return UtilityDB::getMainDB()->exec("UPDATE books SET accesscount = accesscount + 1 WHERE id = $bookID;");
    }

    /**
     * @brief get book (id, title) for given book id
     */
    public static function getBookInfo($bookID)
    {
        return UtilityDB::dbSQLRow(UtilityDB::getMainDB(), "SELECT * FROM books WHERE id = $bookID;");
    }

    /**
     * @brief get subjects (id, title) for given book id under given subject parent id
     */
    public static function getSubjects($bookID, $keywords = null, $parentSubjectID = null, $startAfterID = null, $limit = MAX_RESULT_COUNT) 
    {
        $sqlTitleFilter = UtilityDB::buileSQLLike("title", $keywords);
        
        $sqlParentFilter = "";
        if($parentSubjectID!=null)$sqlParentFilter = "AND parentid = $parentSubjectID";
        
        $sqlStartAfter = "";
        if($startAfterID!=null)$sqlStartAfter = " AND id>$startAfterID";
        
        $titles = UtilityDB::dbSQLRows(UtilityDB::getBookDB($bookID), "SELECT id, title, pageid FROM titles WHERE  1 $sqlTitleFilter $sqlParentFilter $sqlStartAfter ORDER BY id LIMIT $limit;", true);        
        
        $titlesIDs = "";
        foreach ($titles as $title) {
            if(strlen($titlesIDs)>0) {
                $titlesIDs = $titlesIDs.", $title->id";
            } else {
                $titlesIDs = "$title->id";
            }
        }
        
        $parentsTitlesIDs = UtilityDB::dbSQLArray(UtilityDB::getBookDB($bookID), "SELECT DISTINCT parentid FROM titles WHERE  parentid IN($titlesIDs) ORDER BY parentid;");
        foreach ($titles as $title) {
            if(array_search($title->id, $parentsTitlesIDs)!==false) {
                $title->hasChilds = 1;
            } else {
                $title->hasChilds = 0;
            }
        }
        
        return $titles;
    }
    
    /**
     * @brief get title (partnumber, pagenumber) for given book id, title id
     */
    public static function getTitleReference($bookID, $titleID)
    {
        return UtilityDB::dbSQLRow(UtilityDB::getBookDB($bookID), "SELECT partnumber, pagenumber FROM titles WHERE id = $titleID;");
    }
    
    /**
     * @brief get book first page (partnumber, pagenumber)
     */
    public static function getBookFirstPart($bookID)
    {
        return UtilityDB::dbSQLRow(UtilityDB::getBookDB($bookID), "SELECT partnumber FROM pages ORDER BY partnumber limit 1;");
    }    
    
    /**
     * @brief get book first page id
     */
    public static function getBookFirstPageID($bookID)
    {
        return UtilityDB::dbSQLVal(UtilityDB::getBookDB($bookID), "SELECT id FROM pages ORDER BY id limit 1;");
    }    
    
    /**
     * @brief get book last page id
     */
    public static function getBookLastPageID($bookID)
    {
        return UtilityDB::dbSQLVal(UtilityDB::getBookDB($bookID), "SELECT id FROM pages ORDER BY id DESC limit 1;");
    }   
    
    /**
     * @brief get book first page id
     */
    public static function getBookNextPageID($bookID, $pageID)
    {
        return UtilityDB::dbSQLVal(UtilityDB::getBookDB($bookID), "SELECT id FROM pages WHERE id>$pageID ORDER BY id limit 1;");
    }    
    
    /**
     * @brief get book last page id
     */
    public static function getBookPreviousPageID($bookID, $pageID)
    {
        return UtilityDB::dbSQLVal(UtilityDB::getBookDB($bookID), "SELECT id FROM pages WHERE id<$pageID ORDER BY id DESC limit 1;");
    }   

    /**
     * @brief get page info
     */
    public static function getPageInfo($bookID, $pageID)
    {
        return UtilityDB::dbSQLRow(UtilityDB::getBookDB($bookID), "SELECT id, partnumber, pagenumber FROM pages WHERE id = $pageID;");
    }    

    /**
     * @brief get parts list for given book id
     */
    public static function getPartsNumbers($bookID)
    {
        return UtilityDB::dbSQLArray(UtilityDB::getBookDB($bookID), "SELECT DISTINCT(partnumber) FROM pages ORDER BY partnumber;");
    }

    /**
     * @brief get page count given book id
     */
    public static function getBookPageCount($bookID) {
        return UtilityDB::dbSQLVal(UtilityDB::getBookDB($bookID), "SELECT count(*) FROM pages;");
    }
    
    /**
     * @brief get navigation pages numbers (page, first, last, next, previous) for the given page. If give pageNumber is null it assueme it is first page
     */
    public static function getPagesNumbers($bookID, $partNumber)
    {
        return UtilityDB::dbSQLArray(UtilityDB::getBookDB($bookID), "SELECT pagenumber FROM pages WHERE partnumber = $partNumber ORDER BY pagenumber;");
    }

    /**
     * @brief get page for the given book id, part number, page number
     */
    public static function getPage($bookID, $pageID)
    {
        return UtilityDB::dbSQLVal(UtilityDB::getBookDB($bookID), "SELECT page FROM pages WHERE id = $pageID;");
    }
    
    /**
     * @brief get page for the given book id, part number, page number
     */
    public static function getDBInfo()
    {
        $dbInfo = array();
        $dbInfo["nbooks"] = UtilityDB::dbSQLVal(UtilityDB::getMainDB(), "SELECT count(*) FROM books;");
        $dbInfo["ncategories"] = UtilityDB::dbSQLVal(UtilityDB::getMainDB(), "SELECT count(*) FROM categories;");
        $dbInfo["nauthors"] = UtilityDB::dbSQLVal(UtilityDB::getMainDB(), "SELECT count(*) FROM authors;");
        return $dbInfo;
    }
    
    /**
     * @brief search
     */
    public static function search($bookID = 0, $keywords = "", $option = "", $startAfterID = 0, $limit = MAX_RESULT_COUNT) 
    {
        if($option=="exact")
            $sqlBodyFilter = UtilityDB::buileFTSSQLMatch("searchtext", $keywords);
        else
            $sqlBodyFilter = UtilityDB::buileSQLLike("searchtext", $keywords);

        $sqlBookFilter = "";
        if($bookID!=0)$sqlBookFilter = "AND bookid = $bookID";
        
        $sqlStartAfter = "";
        if($startAfterID!=null)$sqlStartAfter = " AND docid > $startAfterID";

        $ftsDB = UtilityDB::getFTSDB();
        if($ftsDB===false)return false;
        
        return UtilityDB::dbSQLRows($ftsDB, "SELECT docid, bookid, pageid, searchtext FROM pagesfts WHERE 1 $sqlBookFilter $sqlStartAfter $sqlBodyFilter LIMIT $limit;", false);
    }
    
    public static function saveUserPreference($userEmail, $userPreferenceList)
    {
        $db = UtilityDB::getUsersDB();
        
        $result = intval(UtilityDB::dbSQLVal($db, "SELECT count(*) FROM userspreferences WHERE email='$userEmail'"));
        if($result===0)
        {
            return $db->query("INSERT INTO userspreferences(email, preferences) VALUES('$userEmail', '$userPreferenceList');");
        }
        else
        {
            return $db->query("UPDATE userspreferences SET preferences = '$userPreferenceList' WHERE email = '$userEmail';");
        }
    }
    
    public static function loadUserPreference($userEmail)
    {
        return UtilityDB::dbSQLVal(UtilityDB::getUsersDB(), "SELECT preferences FROM userspreferences WHERE email='$userEmail'");
    }
    
    public static function getSimilarWords($word, $limit = MAX_RESULT_COUNT)
    {
        return UtilityDB::dbSQLArray(UtilityDB::getFTSDB(), "SELECT word FROM words WHERE word like ('%$word%') LIMIT $limit;", false);
    } 
}
