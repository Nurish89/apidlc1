<?php

$original_issue_date = $subData[0]['appInfo']['idIssueDate'];
$original_birthday = $subData[0]['appInfo']['birthday'];
 
// Creating timestamp from given date
$timestamp1 = strtotime($original_issue_date);
$timestamp2 = strtotime($original_birthday);
 
// Creating new date format from that timestamp
$new_issue_date = date("d-m-Y", $timestamp1);
$new_birthday = date("d-m-Y", $timestamp2);


$html = '
<html>
<head>
<style>
  body { font-family: DejaVu Sans; }
</style>
</head>
<body>
    <table width="100%" align="center">
        <tr>
            <td>
                <table width="100%">
                    <tr>    
                        <td align="center"><h1 style="margin: 0em;">BIÊN BẢN XÁC NHẬN GIAO DỊCH</h1></td>
                    </tr>
                </table>    
                <table>
                    <tr>
                        <td>Hôm nay, ngày '.date('d-m-Y').', các bên gồm có:</td>
                    </tr>
                </table>
                <br>
                <table>
                    <tr>
                        <td><b>Bên A: CÔNG TY TNHH COMPASIA VIỆT NAM</b></td>
                    </tr>
                </table>
                <table>
                    <tr>
                        <td>-	Giấy chứng nhận đăng ký doanh nghiệp số 0315845875 do Sở Kế hoạch và Đầu tư cấp ngày 13/08/2019.</td>
                    </tr>
                    <tr>
                        <td>-	Địa chỉ: Tầng 5, Tòa nhà Nam Việt, 261 Hoàng Văn Thụ, Phường 2, Quận Tân Bình, Thành phố Hồ Chí Minh, Việt Nam</td>
                    </tr>
                    <tr>
                        <td>-	Điện thoại: +84 (28) 3636 0600</td>
                    </tr>
                    <tr>
                        <td>-	Đại diện bởi: Julius Lim Sheng Loong</td>
                    </tr>
                    <tr>
                        <td>-	Chức vụ: Tổng giám đốc điều hành</td>
                    </tr>
                </table>
                <br>    
                <table>
                    <tr>
                        <td><b>Bên B: '.$subData[0]['customerName'].'</b></td>
                    </tr>
                </table>
                <table width="100%">
                    <tr>
                        <td>CMND số: '.$subData[0]['appInfo']['idCard'].'</td>
                        <td>Cấp ngày: '.$new_issue_date.'</td>
                    </tr>        
                    <tr>
                        <td>Điện thoại: '.$subData[0]['appInfo']['mobilephone'].'</td>
                        <td>Ngày tháng năm sinh: '.$new_birthday.'</td>
                    </tr>
                </table>
                <table>
                    <tr><td>Cùng thỏa thuận, xác nhận và thống nhất nội dung sau:</td></tr>
                    <tr><td>-	Bên B đã mua và nhận một (01) điện thoại di động tại cửa hàng '.$storeData[0]['name'].' vào lúc '.date('d-m-Y').' với các thông tin chi tiết như sau:</td></tr>
                    <tr>
                        <td>
                            <ul>
				<li>Chi tiết sản phẩm: '.$subData[0]['productName'].' (IMEI:'.$subData[0]['imei'].') </li>
                                <li>Giá mua sản phẩm đã bao gồm VAT (VNĐ): '.number_format($priceA['drp']).'</li>
                            </ul>        
                        </td>
                    </tr>
                    <tr><td>-	Bên B đã thanh toán khoản trả trước cho Bên A là (VNĐ): '.number_format($subData[0]['appInfo']['downpayment']).' </td></tr>
                    <tr><td>-	Khoản thanh toán còn lại là '.number_format(($priceA['fdc']-$subData[0]['appInfo']['downpayment'])).' (VNĐ) sẽ được thanh toán bởi Ngân hàng TMCP Phương Đông theo hợp đồng tín dụng có mã số '.$subData[0]['contractId'].' giữa bên B và Ngân hàng TMCP Phương Đông (OCB).</td></tr>
                    <tr><td>Biên bản xác nhận này được lập thành ba (03) bản, có giá trị pháp lý như nhau, bên A giữ hai (02) bản và bên B giữ (01) bản.</td></tr>
                </table>
		<br><br>
                <table width="100%">
                    <tr>
                        <td align="center" width="50%"><b>
                            Đại diện bên A theo</b><br>
                            Ủy quyền bởi CompAsia<br>
                            (Nhân viên cửa hàng)<br>
                          (Ký,y) ghi rõ họ tên, mã số nhân viên và đóng dấu cửa hàng tại đâ<br>
                        '.$employeeData[0]['fullName'].' ('.$employeeData[0]['userId'].')
                        </td>
                        <td align="center"><b>
                            Bên B/ khách hàng<br>
                        </b>
                            (ký và ghi rõ họ tên)<br>
			    Tôi đã đọc, hiểu rõ và đồng ý với những điều khoản và điều kiện của hợp đồng này
                        </td>
                    </tr>
                </table>
            </td>        
        </tr>        
    </table>
</body>
</html>';

?>
