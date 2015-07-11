<?php
session_start();
if (empty($_SESSION['namauser']) AND empty($_SESSION['passuser'])){
	header('location:404.php');
}else{
include_once '../../../po-library/po-database.php';
include_once '../../../po-library/po-function.php';

$tableroleaccess = new PoTable('user_role');
$currentRoleAccess = $tableroleaccess->findByAnd(id_level, $_SESSION['leveluser'], module, library);
$currentRoleAccess = $currentRoleAccess->current();

if($currentRoleAccess->read_access == "Y"){

    $aColumns = array( 'id_media', 'file_name', 'file_type', 'file_size', 'date' );

    $sIndexColumn = "id_media";

    $sTable = "media";

    $gaSql['user']       = DATABASE_USER;
    $gaSql['password']   = DATABASE_PASS;
    $gaSql['db']         = DATABASE_NAME;
    $gaSql['server']     = DATABASE_HOST;
	$gaSql['port']     	 = DATABASE_PORT;

    $gaSql['link'] = pg_connect(
        " host=".$gaSql['server'].
		" port=".$gaSql['port'].
        " dbname=".$gaSql['db'].
        " user=".$gaSql['user'].
        " password=".$gaSql['password']
    ) or die('Could not connect: ' . pg_last_error());

    $sLimit = "";
    if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
    {
        $sLimit = "LIMIT ".intval( $_GET['iDisplayLength'] )." OFFSET ".
            intval( $_GET['iDisplayStart'] );
    }

    if ( isset( $_GET['iSortCol_0'] ) )
    {
        $sOrder = "ORDER BY  ";
        for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
        {
            if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
            {
                $sOrder .= $aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."
                    ".($_GET['sSortDir_'.$i]==='asc' ? 'asc' : 'desc').", ";
            }
        }
         
        $sOrder = substr_replace( $sOrder, "", -2 );
        if ( $sOrder == "ORDER BY" )
        {
            $sOrder = "";
        }
    }

    $sWhere = "";
    if ( $_GET['sSearch'] != "" )
    {
        $sWhere = "WHERE (";
        for ( $i=0 ; $i<count($aColumns) ; $i++ )
        {
            if ( $_GET['bSearchable_'.$i] == "true" )
            {
				if ($aColumns[$i] == 'id_media' OR $aColumns[$i] == 'date') {
					$wcol = "cast({$aColumns[$i]} as text)";
				} else {
					$wcol = $aColumns[$i];
				}
                $sWhere .= $wcol." ILIKE '%".pg_escape_string( $_GET['sSearch'] )."%' OR ";
            }
        }
        $sWhere = substr_replace( $sWhere, "", -3 );
        $sWhere .= ")";
    }

    for ( $i=0 ; $i<count($aColumns) ; $i++ )
    {
        if ( $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' )
        {
            if ( $sWhere == "" )
            {
                $sWhere = "WHERE ";
            }
            else
            {
                $sWhere .= " AND ";
            }
			if ($aColumns[$i] == 'id_media' OR $aColumns[$i] == 'date') {
				$wcol = "cast({$aColumns[$i]} as text)";
			} else {
				$wcol = $aColumns[$i];
			}
            $sWhere .= $wcol." ILIKE '%".pg_escape_string($_GET['sSearch_'.$i])."%' ";
        }
    }

    $sQuery = "
        SELECT ".str_replace(" , ", " ", implode(", ", $aColumns))."
        FROM   $sTable
        $sWhere
        $sOrder
        $sLimit
    ";
	$rResult = pg_query( $gaSql['link'], $sQuery ) or die(pg_last_error());

    $sQuery = "
        SELECT $sIndexColumn
        FROM   $sTable
    ";
    $rResultTotal = pg_query( $gaSql['link'], $sQuery ) or die(pg_last_error());
    $iTotal = pg_num_rows($rResultTotal);
    pg_free_result( $rResultTotal );
     
    if ( $sWhere != "" )
    {
        $sQuery = "
            SELECT $sIndexColumn
            FROM   $sTable
            $sWhere
        ";
        $rResultFilterTotal = pg_query( $gaSql['link'], $sQuery ) or die(pg_last_error());
        $iFilteredTotal = pg_num_rows($rResultFilterTotal);
        pg_free_result( $rResultFilterTotal );
    }
    else
    {
        $iFilteredTotal = $iTotal;
    }

    $output = array(
        "sEcho" => intval($_GET['sEcho']),
        "iTotalRecords" => $iTotal,
        "iTotalDisplayRecords" => $iFilteredTotal,
        "aaData" => array()
    );

	$no = 1;
    while ( $aRow = pg_fetch_array($rResult, null, PGSQL_ASSOC) )
    {
        $row = array();
		$tableroleaccess = new PoTable('user_role');
		$currentRoleAccess = $tableroleaccess->findByAnd(id_level, $_SESSION['leveluser'], module, 'library');
		$currentRoleAccess = $currentRoleAccess->current();
        for ( $i=1 ; $i<count($aColumns) ; $i++ )
        {
			if($currentRoleAccess->delete_access == "Y"){
				$tbldelete = "<a class='btn btn-xs btn-danger alertdel' id='$aRow[id_media]'><i class='fa fa-times'></i></a>";
			}
			$checkdata = "<div class='text-center'><input type='checkbox' id='titleCheckdel' /><input type='hidden' class='deldata' name='item[$no][deldata]' value='$aRow[id_media]' disabled></div>";
			$row[] = $checkdata;
			$row[] = $aRow['id_media'];
			$row[] = "<a href='../po-content/po-upload/$aRow[file_name]'>$aRow[file_name]</a>";
			$row[] = $aRow['file_type'];
			$row[] = $aRow['file_size']." Byte";
			$row[] = $aRow['date'];
			$row[] = "<div class='text-center'><div class='btn-group btn-group-xs'>
					$tbldelete
			</div></div>";
        }
        $output['aaData'][] = $row;
	$no++;
    }

    echo json_encode( $output );

	pg_free_result( $rResult );

    pg_close( $gaSql['link'] );
}
}
?>