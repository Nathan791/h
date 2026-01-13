<?php
require('fpdf/fpdf.php');
$db=new mysqli("localhost","root","","commerce");

$order=(int)$_GET['id'];
$res=$db->query("SELECT * FROM orders WHERE id=$order");
$o=$res->fetch_assoc();

$pdf=new FPDF();
$pdf->AddPage();
$pdf->SetFont("Arial","B",16);
$pdf->Cell(0,10,"INVOICE #$order",0,1);

$items=$db->query("SELECT p.name,i.qty,i.price 
FROM order_items i JOIN products p ON p.id=i.product_id
WHERE order_id=$order");

$pdf->SetFont("Arial","",12);
while($i=$items->fetch_assoc()){
    $pdf->Cell(0,8,"{$i['name']} x{$i['qty']} - {$i['price']}$",0,1);
}
$pdf->Cell(0,10,"Total: {$o['total']}$",0,1);
$pdf->Output();
?>