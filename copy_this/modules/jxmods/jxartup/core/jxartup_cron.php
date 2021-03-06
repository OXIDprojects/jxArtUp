<?php

/*
 *    This file is part of the module jxArtUp for OXID eShop Community Edition.
 *
 *    The module jxArtUp for OXID eShop Community Edition is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    The module jxArtUp for OXID eShop Community Edition is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with OXID eShop Community Edition.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link      https://github.com/job963/jxArtUp
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @copyright (C) Joachim Barthel 2014-2016
 *
 */

class jxArtUpCron
{
    protected $dbh;

    public function __construct()
    {
        require_once '../../../../config.inc.php';
            
        $dbConn = 'mysql:host='.$this->dbHost.';dbname='.$this->dbName;
        $dbUser = $this->dbUser;
        $dbPass = $this->dbPwd;        
        $this->dbh = new PDO($dbConn, $dbUser, $dbPass); 
        $this->dbh->exec('set names "utf8"');
    }
    
    
    public function __destruct() 
    {
        $this->dbh = NULL;
    }

    public function updateProducts()
    {
        $sSql = "SELECT jxid, jxartid, jxfield1, jxtype1, jxvalue1, jxfield2, jxtype2, jxvalue2, jxfield3, jxtype3, jxvalue3, jxinherit, "
                . "oxid, oxparentid, oxvarcount "
                . "FROM jxarticleupdates, oxarticles "
                . "WHERE "
                    . "jxartid = oxid "
                    . "AND jxupdatetime <= NOW() "
                    . "AND jxdone != 1 "
                . "ORDER BY jxupdatetime ASC ";
        
        $stmt = $this->dbh->prepare($sSql);
        $stmt->execute();
        $aTasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ( count($aTasks) == 0 )
            echo 'nothing to do';
        
        foreach ($aTasks as $key => $aTask) {
            $aSet = array();
            for ($i=1; $i<=3; $i++) {
                if ( $aTask["jxfield{$i}"] != 'none' ) {
                    if ($aTask["jxtype{$i}"] == 'FLOAT')
                        array_push ($aSet, "{$aTask["jxfield{$i}"]} = {$aTask["jxvalue{$i}"]}");
                    elseif  ($aTask["jxtype{$i}"] == 'CHAR')
                        array_push ($aSet, "{$aTask["jxfield{$i}"]} = '{$aTask["jxvalue{$i}"]}'");
                    elseif  ($aTask["jxtype{$i}"] == 'INT')
                        array_push ($aSet, "{$aTask["jxfield{$i}"]} = {$aTask["jxvalue{$i}"]}");
                }
            }
            
            $sSql = "UPDATE oxarticles SET " . implode( ', ', $aSet ) . " WHERE oxid = '{$aTask['jxartid']}'";
            $this->_logAction( 'jxartup_cron: ' . $sSql );
            try {
                $stmt = $this->dbh->prepare($sSql);
                $stmt->execute();
                if ($stmt->errorCode() != '00000') {
                    $this->_logAction( 'SQL Error on: ' . $sSql . '<br>' );
                    $this->_logAction( $stmt->errorInfo() . '<br>' );
                }
            }
            catch (PDOException $e) {
                echo('pdo->execute error = '.$e->getMessage(). '<br>' );
                die();
            }
             
            // Are there variants to update ?
            if ($aTask['jxinherit'] == 1) {
                $sSql = "UPDATE oxarticles SET " . implode( ', ', $aSet ) . " WHERE oxparentid = '{$aTask['jxartid']}'";
                $this->_logAction( 'jxartup_cron: ' . $sSql );
                try {
                    $stmt = $this->dbh->prepare($sSql);
                    $stmt->execute();
                    if ($stmt->errorCode() != '00000') {
                        $this->_logAction( 'SQL Error on: ' . $sSql );
                        $this->_logAction( $stmt->errorInfo() );
                    }
                }
                catch (PDOException $e) {
                    echo('pdo->execute error = '.$e->getMessage(). '<br>' );
                    die();
                }
            }
            
            // Is it a variant or does it have variants? Update varminprice and varmaxprice
            if ($aTask['oxparentid'] != "") {
                $oxid = $aTask['oxparentid'];
                $sSql = "SELECT MIN(oxprice) AS minprice, MAX(oxprice) AS maxprice  FROM oxarticles WHERE oxparentid = '{$oxid}' ";
            } elseif ($aTask['oxvarcount'] != 0) {
                $oxid = $aTask['oxid'];
                $sSql = "SELECT MIN(oxprice) AS minprice, MAX(oxprice) AS maxprice  FROM oxarticles WHERE oxparentid = '{$oxid}' ";
            }
            if (($aTask['oxparentid'] != "") || ($aTask['oxvarcount'] != 0)){
                // retrieve min / max values
                $stmt = $this->dbh->prepare($sSql);
                $stmt->execute();
                $aVals = $stmt->fetch(PDO::FETCH_ASSOC);

                $sSql = "UPDATE oxarticles SET oxvarminprice = {$aVals['minprice']}, oxvarmaxprice = {$aVals['maxprice']} WHERE oxid = '{$oxid}' ";
                $this->_logAction( 'jxartup_cron: ' . $sSql );
                try {
                    $stmt = $this->dbh->prepare($sSql);
                    $stmt->execute();
                    if ($stmt->errorCode() != '00000') {
                        $this->_logAction( 'SQL Error on: ' . $sSql );
                        $this->_logAction( $stmt->errorInfo() );
                    }
                }
                catch (PDOException $e) {
                    echo('pdo->execute error = '.$e->getMessage(). '<br>' );
                    die();
                }
            }
            
            // Job is done, update the job list
            $sSql = "UPDATE jxarticleupdates SET jxdone = 1 WHERE jxid = '{$aTask['jxid']}'";
            //echo $sSql . '<hr>';
            try {
                $stmt = $this->dbh->prepare($sSql);
                $stmt->execute();
                if ($stmt->errorCode() != '00000') {
                    echo( 'SQL: ' . $sql );
                    echo( $stmt->errorInfo() );
                }
            }
            catch (PDOException $e) {
                echo('pdo->execute error = '.$e->getMessage(). '<br>' );
                die();
            }

        }
    }


    private function _logAction($value)
    {
        $nPathEnd = strpos( $_SERVER['SCRIPT_FILENAME'], '/modules' );
        $sShopPath = substr( $_SERVER['SCRIPT_FILENAME'], 0, $nPathEnd );
        $sLogPath = $sShopPath.'/log/';
        
        $fh = fopen($sLogPath.'jxmods.log',"a+");
        
        if (gettype($value) == 'array' OR gettype($value) == 'object')
            fputs( $fh, print_r($value, TRUE) );
        else
            fputs( $fh, date("Y-m-d H:i:s  ") . $value . "\n" );
        
        fclose($fh);
    }
    
} 

$jxCron = new jxArtUpCron();
$jxCron->updateProducts();
