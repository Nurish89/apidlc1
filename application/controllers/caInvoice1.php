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
  body { font-family: DejaVu Sans; font-size:10px; }
</style>
</head>
<body>
	<table width="100%" align="center">
		<tr>
			<td>
				<table width="100%">
					<tr>
						<td width="30%">
							<img src="data:image/png;base64,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">
						<td>		
						<td width="70%" align="center" style="padding-left:20px;">
							<table width="100%">
							<tr><td align="center"><h4 style="margin-bottom:0px;">CÔNG TY TNHH COMPASIA VIỆT NAM</h4></td></tr>
							<tr><td align="center">Tầng 5, Tòa nhà Nam Việt, 261 Hoàng Văn Thụ, P.2, Q. Tân Bình, TP.HCM</td></tr>
							<tr><td align="center">Điện thoại: (+84 28) 3636 0600&nbsp;&nbsp;&nbsp;&nbsp;Website: www.compasia.com</td></tr>
							</table>	
						</td>		
					</tr>
				</table>
				<table width="100%" style="font-size:13px;">
					<tr>
						<td align="center">
							<h2>BIÊN BẢN XÁC NHẬN GIAO DỊCH</h2>
						</td>
					</td>	
				</table>	
				<table width="100%" style="font-size:13px;">
					<tr>
						<td>
							Hôm nay, ngày '.date('d-m-Y').', các bên gồm có:
						</td>
					</tr>
				</table>
				<table width="100%" style="font-size:13px;">
					<tr>
						<td>
							<h3>Bên A: CÔNG TY TNHH COMPASIA VIỆT NAM</h3>
							<ul style="margin-bottom:0px;">
								<li>Giấy chứng nhận đăng ký doanh nghiệp số 0315845875 do Sở Kế hoạch và Đầu tư cấp ngày 13-08-2019</li>
								<li>Địa chỉ: Tầng 5, Tòa nhà Nam Việt, 261 Hoàng Văn Thụ, Phường 2, Quận Tân Bình, Thành phố Hồ Chí Minh, Việt Nam</li>
								<li>Điện thoại: (+84 28) 3636 0600</li>
								<li>Đại diện bởi: Julius Lim Sheng Loong</li>
								<li>Chức vụ: Tổng giám đốc điều hành</li>
							</ul>	
						</td>	
					</tr>		
				</table>
				<table width="100%" style="font-size:13px;">
					<tr><td><h3>Bên B: '.$subData[0]['customerName'].'</h3></td></tr>
				</table>
				<table width="100%" style="font-size:13px;">
					<tr><td>&emsp;CMND số: '.$subData[0]['appInfo']['idCard'].'</td><td>Cấp ngày: '.$new_issue_date.'</td></tr>
					<tr><td>&emsp;Điện thoại: '.$subData[0]['appInfo']['mobilephone'].'</td><td>Ngày tháng năm sinh: '.$new_birthday.'</td></tr>
				</table>
				<table width="100%" style="font-size:13px;">
					<tr>
						<td>
							<p style="margin-bottom:0px;">Cùng thỏa thuận, xác nhận và thống nhất nội dung sau:</p>
							<ul type="-" style="margin-top:0px;text-align: justify;margin-bottom:0px;">
								<li>Bên B đã mua và nhận một (01) thiết bị '.$subData[0]['productName'].' có IMEI '.$subData[0]['imei'].' tại cửa hàng '.$storeData[0]['name'].' vào ngày '.date('d-m-Y').'. Tổng giá mua thiết bị bao gồm phí tham gia chương trình và thuế GTGT là '.number_format($priceA['fdc']).' VNĐ.</li>
								<li>Bên B đã thanh toán khoản trả trước cho Bên A là: '.number_format($subData[0]['appInfo']['downpayment']).' VNĐ.</li>
								<li>Bên B sẽ thanh toán cho Bên A khoản còn lại là '.number_format(($priceA['fdc']-$subData[0]['appInfo']['downpayment'])).' VNĐ thông qua dịch vụ tín dụng của Ngân hàng TMCP Phương Đông theo hợp đồng tín dụng số '.$subData[0]['contractId'].' giữa Bên B và Ngân hàng TMCP Phương Đông (OCB) với số tiền mỗi tháng là '.number_format($priceA['dmof']).' VNĐ.</li>
								<li>Sau khi Bên B hoàn tất toàn bộ thanh toán theo hợp đồng tín dụng nêu trên, Bên B sẽ nhận ưu đãi nâng cấp thiết bị theo các Điều khoản và Điều kiện đính kèm Biên bản này, dựa trên giá bán lẻ của thiết bị được công bố tại cửa hàng tại thời điểm mua là '.number_format($priceA['drp']).' VNĐ.</li>
							</ul>			
						</td>
				</table>
				<table width="100%" style="font-size:13px;">
					<tr>
						<td>
					<p style="text-align: justify;margin-top:0px;">Biên bản xác nhận này được lập thành ba (03) bản, có giá trị pháp lý như nhau, Bên A giữ hai (02) bản và Bên B giữ một (01) bản.
						</td>
					</tr>
				</table>
				<table width="100%" align="center" style="font-size:13px;">				
					<tr>
						<td>
							<table width="100%" align="center">
								<tr><td align="center"><u><b>Đại diện Bên A</b></u></td><td align="center"><u><b>Bên B/'.$subData[0]['customerName'].'</b></u></td></tr>
								<tr><td align="center">Ủy quyền bởi CompAsia</td><td align="center">Tôi xác nhận đã nhận Biên bản này kèm với Điều khoản</td></tr>
								<tr><td align="center">(Ký, ghi rõ họ tên và đóng dấu cửa hàng)</td><td align="center">và Điều kiện Nâng cấp Thiết bị, đồng thời hiểu rõ và </td></tr>
								<tr><td align="center">'.$employeeData[0]['fullName'].' ('.$employeeData[0]['userId'].')</td><td align="center">đồng ý với nội dung trong các văn bản này.</td></tr>
								<tr><td align="center"></td><td align="center">(Ký và ghi rõ họ tên)</td></tr>
							</table>
						</td>
					</tr>	
				</table>
			</td>	
		</tr>
	</table>	
</body>
</html>';

?>

