<?php
// phpinfo();die;
require_once('dbconfig.php');
set_time_limit(0);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csvfile']) && $_FILES['csvfile']['error'] == 0) {
    
    // Set up variables
    $fileTmpPath    = $_FILES['csvfile']['tmp_name'];
    $fileName       = $_FILES['csvfile']['name'];
    $fileSize       = $_FILES['csvfile']['size'];
    $fileType       = $_FILES['csvfile']['type'];

    // Define allowed file types
    $allowedTypes = ['text/csv', 'application/csv', 'application/vnd.ms-excel'];

    if (in_array($fileType, $allowedTypes) ) {
        
        // Read the CSV file
        if (($handle = fopen($fileTmpPath, 'r')) !== FALSE) {
            
            $headers   = fgetcsv($handle);
            $rowNumber = 0;

     
            $sql    = "Select count(*) Total FROM temporary_completedata";
            $result = $db->query($sql);
            $row    = $result->fetch_assoc();
  

            if($row['Total'] == 0) {

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                
                    $rowNumber++;

                    // Skip the first 6 rows
                    if ($rowNumber <= 5) {
                        continue; 
                    }

                    $bindTypes    = str_repeat("s", count($headers));
                    $placeholders = rtrim(str_repeat("?,", count($headers)), ",");

                    $stmt   = $db->prepare("INSERT INTO temporary_completedata (`sr`,
                                                                                `date`,
                                                                                `academic_year`,
                                                                                `session`,
                                                                                `alloted_category`,
                                                                                `voucher_type`,
                                                                                `voucher_no`,
                                                                                `roll_no`,
                                                                                `admno_uniqueid`,
                                                                                `status`,
                                                                                `fee_category`,
                                                                                `faculty`,
                                                                                `program`,
                                                                                `department`,
                                                                                `batch`,
                                                                                `receipt_no`,
                                                                                `fee_head`,
                                                                                `due_amount`,
                                                                                `paid_amount`,
                                                                                `concession_amount`,
                                                                                `scholarship_amount`,
                                                                                `reverse_concession_amount`,
                                                                                `write_off_amount`,
                                                                                `adjusted_amount`,
                                                                                `refund_amount`,
                                                                                `fund_transfer_amount`,
                                                                                `remarks`) 
                                                                    VALUES ($placeholders)
                                            ");

                    $stmt->bind_param($bindTypes, ...$data);
                    $stmt->execute();
                }
            }

            fclose($handle);

            /* Branches */
            $del_sql1 = "delete from branches";
            $db->query($del_sql1);

            $sql2 = "SELECT DISTINCT faculty
                       FROM temporary_completedata
                      WHERE faculty <> '' or faculty <> null";

            $result = $db->query($sql2);
            
            if($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    
                    $sql1   = "INSERT INTO branches (id,branch_name)
                                            VALUES  ( (SELECT (IFNULL(MAX(id), 0)+1) FROM branches a),
                                                    '{$row['faculty']}'
                                                    )
                            ";

                    $db->query($sql1);
                }
            }

            /* Feecategory */
            $del_sql1 = "delete from feecategory";
            $db->query($del_sql1);

            $sql2 = "SELECT distinct fee_category,b.id br_id
                       FROM temporary_completedata a, branches b 
                      WHERE fee_category <> '' or fee_category <> null 
                      ORDER BY fee_category,b.id
                    ";

            $result = $db->query($sql2);
            
            if($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    
                    $sql1   = "INSERT INTO feecategory (id,fee_category,br_id )
                                               VALUES  ( (SELECT (IFNULL(MAX(id), 0)+1) FROM feecategory a WHERE a.fee_category<>'{$row['fee_category']}'),
                                                        '{$row['fee_category']}',
                                                         {$row['br_id']}
                                                        )
                            ";

                    $db->query($sql1);
                }
            }

            /* Feecollectiontypes */

         
            $del_sql1 = "delete from feecollectiontype";
            $db->query($del_sql1);

            $sql2 = "SELECT module_name,b.id br_id 
                       FROM module a, branches b 
                      order by module_name,b.id
                    ";

            $result = $db->query($sql2);
            
            if($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    
                    $sql1   = "INSERT INTO feecollectiontype (id,collection_head,collection_desc,br_id )
                                               VALUES  ( (SELECT (IFNULL(MAX(id), 0)+1) FROM feecollectiontype a WHERE a.collection_head <> '{$row['module_name']}'),
                                                        '{$row['module_name']}',
                                                        '{$row['module_name']}',
                                                         {$row['br_id']}
                                                        )
                            ";

                    $db->query($sql1);
                }
            }

            
            /* Feetypes */
       
            $del_sql1 = "delete from feetypes";
            $db->query($del_sql1);

            $sql2 = "SELECT distinct fee_head,b.id br_id
                       FROM temporary_completedata a, branches b 
                      WHERE fee_head <> '' or fee_head <> null
                    ";

            $result = $db->query($sql2);
            
            if($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    
                    $sql1   = "INSERT INTO feetypes (id,	
                                                    fee_category,	
                                                    f_name,	
                                                    collection_id,	
                                                    br_id,	
                                                    seq_id,	
                                                    fee_type_ledger,	
                                                    fee_headtype
                                                    )
                                            VALUES  (  (SELECT IFNULL(MAX(id), 0) + 1 FROM feetypes a),
                                                       (SELECT id 
                                                          from feecategory 
                                                         where fee_category = 'General' 
                                                           and br_id        = {$row['br_id']}),
                                                        '{$row['fee_head']}',
                                                       (SELECT id 
                                                          FROM feecollectiontype 
                                                         where collection_head = 'academic' 
                                                           and br_id           ={$row['br_id']}),
                                                       {$row['br_id']},
                                                       (SELECT IFNULL(MAX(seq_id), 0) + 1 
                                                          FROM feetypes a 
                                                         WHERE f_name <> '{$row['fee_head']}'
                                                        ),
                                                       '{$row['fee_head']}',
                                                       (SELECT module_id 
                                                          from module 
                                                         where module_name =   ( select collection_head
                                                                                    from feecollectiontype
                                                                                   where collection_head = 'academic' 
                                                                                     and br_id           = {$row['br_id']}
                                                                                )
                                                        )
                                                    )
                            ";

                    $db->query($sql1);
                }
            }


            /* financial_trans */

            $del_sql1 = "delete from financial_transdetail";
            $db->query($del_sql1);
        
            $del_sql2 = "delete from financial_trans";
            $db->query($del_sql2);

            $sql2 = "SELECT cc.moduleid,
                            (select floor(rand()*10000) from dual) transid,
                            `admno_uniqueid` admno, 		     
                            SUM(CASE WHEN a.voucher_type='DUE' and crdr='D' and entrymodeno=0 then IFNULL(`due_amount`,0)
                                     WHEN a.voucher_type='CONCESSION' and crdr='C' and entrymodeno=15 then ifnull(`concession_amount`,0)
                                     WHEN a.voucher_type='SCHOLARSHIP' and crdr='C' and entrymodeno=15 then ifnull(`scholarship_amount`,0)
                                     WHEN a.voucher_type='REVDUE' and crdr='C' and entrymodeno=12 then ifnull(`write_off_amount`,0)
                                     WHEN a.voucher_type='SCHOLARSHIPREV/REVCONCESSION' and crdr='D' and entrymodeno=16 then ifnull(reverse_concession_amount,0)
                                END    
                            ) amount,
                            crdr, 
                            str_to_date(date,'%d-%m-%Y') trandate,
                            `academic_year` acdyear,
                            entrymodeno,
                            voucher_no voucherno,
                            dd.brid,
                            case when a.voucher_type= 'SCHOLARSHIP' then 2
                                 when a.voucher_type= 'CONCESSION' then 1
                                 else 'NULL'
                            end typeofconcession,
                            a.voucher_type
                     from `temporary_completedata` a
                            left join (select id brid,branch_name
                                        from branches c
                                     ) dd on dd.branch_name = a.faculty,
                           entrymode b,
                           (select module_id moduleid
                              from module c
                             where module_name = 'academic'
                            ) cc
                    WHERE a.voucher_type in ('DUE','REVDUE','SCHOLARSHIP','SCHOLARSHIPREV/REVCONCESSION','CONCESSION')
                      and a.voucher_type = b.entry_modename
                    group by voucher_no,a.admno_uniqueid
                    order by voucher_no,a.admno_uniqueid";

            $result = $db->query($sql2);
            
            if($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    
                        /* Parent Record */

                        // ID genarate
                        $sql_id    = "SELECT (IFNULL(MAX(id), 0)+1) AS ID FROM financial_trans a";
                        $result_id = $db->query($sql_id);
                        $idd        = $result_id->fetch_assoc();
                        
                        if($row['brid'] != '')
                        {
                            $brid = $row['brid'];
                        }
                        else {
                            $brid = "'NULL'";
                        }

                        $sql_hd   = "INSERT INTO financial_trans (  id,	
                                                                    moduleid,	
                                                                    transid,	
                                                                    admno,	
                                                                    amount,	
                                                                    crdr,	
                                                                    trandate,	
                                                                    acdyear,
                                                                    entrymode,
                                                                    voucherno,
                                                                    brid,
                                                                    typeofconcession
                                                                )
                                                VALUES  (   {$idd['ID']},
                                                            {$row['moduleid']},
                                                            {$row['transid']},
                                                            '{$row['admno']}',
                                                            {$row['amount']},
                                                            '{$row['crdr']}',
                                                            '{$row['trandate']}',
                                                            '{$row['acdyear']}',
                                                            {$row['entrymodeno']},
                                                            {$row['voucherno']},
                                                            {$brid},
                                                            {$row['typeofconcession']}
                                                        )
                                ";

                        $db->query($sql_hd);

                        
                        $crdr = $row['crdr'];

                        /* Child Record */
                        $sql_detail = "SELECT   (CASE WHEN a.voucher_type='DUE' and '{$crdr}'='D' and {$row['entrymodeno']}=0 then IFNULL(`due_amount`,0)
                                                        WHEN a.voucher_type='CONCESSION' and '{$crdr}'='C' and {$row['entrymodeno']}=15 then ifnull(`concession_amount`,0)
                                                        WHEN a.voucher_type='SCHOLARSHIP' and '{$crdr}'='C' and {$row['entrymodeno']}=15 then ifnull(`scholarship_amount`,0)
                                                        WHEN a.voucher_type='REVDUE' and '{$crdr}'='C' and {$row['entrymodeno']}=12 then ifnull(`write_off_amount`,0)
                                                        WHEN a.voucher_type='SCHOLARSHIPREV/REVCONCESSION' and '{$crdr}'='D' and {$row['entrymodeno']}=16 then ifnull(reverse_concession_amount,0)
                                                    END    
                                                ) amount,
                                              fee_head head_name,
                                              ifnull(b.seq_id,'null') headid
                                         from `temporary_completedata` a
                                                left join (SELECT seq_id,f_name 
                                                             from feetypes bb 
                                                            where IFNULL(bb.br_id,'')  = IFNULL({$brid},'')
                                                          ) b on b.f_name = a.fee_head
                                        WHERE a.voucher_type       = '{$row['voucher_type']}'
                                          and a.voucher_no         =  {$row['voucherno']}
                                          and a.admno_uniqueid     = '{$row['admno']}'
                                          
                                    ";

                        $result_detail = $db->query($sql_detail);

                        if($result_detail->num_rows > 0) {
                        while($row_detail = $result_detail->fetch_assoc()) {

                        $sql_detail1   = "INSERT INTO financial_transdetail (   id,	
                                                                                financialtranid,	
                                                                                moduleid,	
                                                                                amount,	
                                                                                headid,	
                                                                                crdr,	
                                                                                brid,
                                                                                head_name
                                                                            )
                                                            VALUES  (   (SELECT (IFNULL(MAX(id), 0)+1) FROM financial_transdetail a),
                                                                        {$idd['ID']},
                                                                        {$row['moduleid']},
                                                                        {$row_detail['amount']},
                                                                        {$row_detail['headid']},
                                                                        '{$crdr}',
                                                                        {$brid},
                                                                        '{$row_detail['head_name']}'
                                                                    )
                            ";
                        
                            $db->query($sql_detail1);
                        }
                    }
                }
            }

            /* commision */

            $del_sql1 = "delete from common_fee_collection_headwise";
            $db->query($del_sql1);
        
            $del_sql2 = "delete from common_fee_collection";
            $db->query($del_sql2);

            $sql2 = "SELECT cc.moduleid,
                            (select floor(rand()*10000000) from dual) transid,
                            `admno_uniqueid` admno,
                            `roll_no` rollno,
                             SUM(CASE WHEN a.voucher_type in ('RCPT','REVRCPT') then IFNULL(`paid_amount`,0)
                                      WHEN a.voucher_type in ('JV','REVJV') then ifnull(`adjusted_amount`,0)
                                      WHEN a.voucher_type in ('PMT','REVPMT') then ifnull(`refund_amount`,0)
                                      WHEN UPPER(a.voucher_type)=UPPER('Fundtransfer') then ifnull(`fund_transfer_amount`,0)
                                END    
                            ) amount,
                            dd.brid,
                            `academic_year` acadamicyear,
                            `session` financialyear,
                            `receipt_no` displayreceiptno,
                            entrymodeno entrymode,
                            str_to_date(date,'%d-%m-%Y') paiddate,
                            CASE WHEN crdr='C' THEN CASE WHEN voucher_type='RCPT' THEN 0 
                                 						 WHEN voucher_type='JV'   THEN 0
                                                         WHEN voucher_type='REVPMT' THEN 1
                                                         ELSE 'NULL'
                                                    END
                                 WHEN crdr='D' THEN CASE WHEN voucher_type='REVRCPT' THEN 1 
                                 						 WHEN voucher_type='REVJV'   THEN 1
                                                         WHEN voucher_type='PMT'     THEN 0
                                                         ELSE 'NULL'
                                                    END
                                 ELSE 'NULL'
                            END inactive,
                            a.voucher_type,
                            a.tdate
                     from `temporary_completedata` a
                            left join (select id brid,branch_name
                                        from branches c
                                     ) dd on dd.branch_name = a.faculty,
                           entrymode b,
                           (select module_id moduleid
                              from module c
                             where module_name = 'academic'
                            ) cc
                    WHERE UPPER(a.voucher_type) in ('RCPT','REVRCPT','JV','REVJV','PMT','REVPMT',UPPER('Fundtransfer'))
                      and a.voucher_type = b.entry_modename
                    group by receipt_no,a.admno_uniqueid,a.voucher_type,a.tdate
                    order by receipt_no,a.admno_uniqueid,a.voucher_type,a.tdate
                ";
            
            $result = $db->query($sql2);
            
            if($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    
                        /* Parent Record */

                        // ID genarate
                        $sql_id    = "SELECT (IFNULL(MAX(id), 0)+1) AS ID FROM common_fee_collection a";
                        $result_id = $db->query($sql_id);
                        $comm_id   = $result_id->fetch_assoc();
                        
                        if($row['brid'] != '')
                        {
                            $brid = $row['brid'];
                        }
                        else {
                            $brid = "'NULL'";
                        }

                        if($row['displayreceiptno'] != '')
                        {
                            $receiptno = "'".$row['displayreceiptno']."'";
                        }
                        else {
                            $receiptno = "NULL";
                        }

                        $sql_hd   = "INSERT INTO common_fee_collection (    id,	
                                                                            moduleid,	
                                                                            transid,	
                                                                            admno,	
                                                                            rollno,	
                                                                            amount,	
                                                                            brid,	
                                                                            acadamicyear,
                                                                            financialyear,
                                                                            displayreceiptno,
                                                                            entrymode,
                                                                            paiddate,
                                                                            inactive
                                                                       )
                                                VALUES  (   {$comm_id['ID']},
                                                            {$row['moduleid']},
                                                            {$row['transid']},
                                                            '{$row['admno']}',
                                                            '{$row['rollno']}',
                                                             {$row['amount']},
                                                             {$brid},
                                                            '{$row['acadamicyear']}',
                                                            '{$row['financialyear']}',
                                                             {$receiptno},
                                                             {$row['entrymode']},
                                                            '{$row['paiddate']}',
                                                             {$row['inactive']}
                                                        )
                                ";
                        // if($comm_id['ID'] == 8) {
                        //     echo  $sql_hd;die;
                        // }
                        $db->query($sql_hd);

                        
                        //$crdr = $row['crdr'];

                        /* Child Record */
                        $sql_detail = "SELECT  (CASE WHEN a.voucher_type in ('RCPT','REVRCPT') then IFNULL(`paid_amount`,0)
                                                     WHEN a.voucher_type in ('JV','REVJV') then ifnull(`adjusted_amount`,0)
                                                     WHEN a.voucher_type in ('PMT','REVPMT') then ifnull(`refund_amount`,0)
                                                     WHEN UPPER(a.voucher_type)=UPPER('Fundtransfer') then ifnull(`fund_transfer_amount`,0)
                                                END    
                                                ) amount,
                                              fee_head head_name,
                                              ifnull(b.seq_id,'null') headid
                                         from `temporary_completedata` a
                                                left join (SELECT seq_id,f_name 
                                                             from feetypes bb 
                                                            where IFNULL(bb.br_id,'')  = IFNULL({$brid},'')
                                                          ) b on b.f_name = a.fee_head
                                        WHERE a.admno_uniqueid     = '{$row['admno']}'
                                          and UPPER(a.voucher_type)       = UPPER('{$row['voucher_type']}')
                                          and ( ({$receiptno} IS NOT NULL  AND a.receipt_no={$receiptno})
                                               OR
                                                ({$receiptno} IS NULL)
                                              )
                                          and a.tdate = '{$row['tdate']}'
                                    ";

                        $result_detail = $db->query($sql_detail);

                        if($result_detail->num_rows > 0) {
                        while($row_detail = $result_detail->fetch_assoc()) {

                        $sql_detail1   = "INSERT INTO common_fee_collection_headwise (  id,	
                                                                                        receiptid,	
                                                                                        moduleid,
                                                                                        headid,
                                                                                        head_name,	
                                                                                        brid,	
                                                                                        amount
                                                                                    )
                                                                            VALUES  (   (SELECT (IFNULL(MAX(id), 0)+1) FROM common_fee_collection_headwise a),
                                                                                        {$comm_id['ID']},
                                                                                        {$row['moduleid']},
                                                                                        {$row_detail['headid']},
                                                                                        '{$row_detail['head_name']}',
                                                                                        {$brid},
                                                                                        {$row_detail['amount']}
                                                                                    )
                            ";
                        
                            $db->query($sql_detail1);
                        }
                    }
                }
            }

            echo "File imported Successfully! ";

        } else {
            echo "Error opening the file.";
        }
    } else {
        echo "Invalid file type or file too large.";
    }
} else {
    echo "No file uploaded or there was an upload error.";
}
?>