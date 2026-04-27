<?php 
function tgl_indo($tgl) {
	$tanggal = date("j", strtotime($tgl));
	$bulan = get_bulan(date("n", strtotime($tgl)));
	$tahun = date("Y", strtotime($tgl));
	$jam = date("H", strtotime($tgl));
	$menit = date("i", strtotime($tgl));
	return $tanggal.' '.$bulan.' '.$tahun;
}
function tgljm_full($tgl) {
	$tanggal = date("j", strtotime($tgl));
	$bulan = get_bulan(date("n", strtotime($tgl)));
	$tahun = date("Y", strtotime($tgl));
	$jam = date("H", strtotime($tgl));
	$menit = date("i", strtotime($tgl));
	return $tanggal.' '.$bulan.' '.$tahun.', '.$jam.':'.$menit;
}

function tgl_full($tgl) {
	$tanggal = date("j", strtotime($tgl));
	$bulan = get_bulan(date("n", strtotime($tgl)));
	$tahun = date("Y", strtotime($tgl));
	return $tanggal.' '.$bulan.' '.$tahun;
}


//tanggal dengan bulan di singkat
function tgljm_bln($tgl) {
	$tanggal = date("j", strtotime($tgl));
	$bulan = get_bln(date("n", strtotime($tgl)));
	$tahun = date("Y", strtotime($tgl));
	$jam = date("H", strtotime($tgl));
	$menit = date("i", strtotime($tgl));
	return $tanggal.'-'.$bulan.'-'.$tahun.', '.$jam.':'.$menit;
}

function tgl_bln($tgl) {
	$tanggal = date("j", strtotime($tgl));
	$bulan = get_bln(date("n", strtotime($tgl)));
	$tahun = date("Y", strtotime($tgl));
	return $tanggal.'-'.$bulan.'-'.$tahun;
}

//tanggal dengan bulan angka
function tgljm_angka($tgl) {
	$tanggal = date("j", strtotime($tgl));
	$bulan = date("m", strtotime($tgl));
	$tahun = date("Y", strtotime($tgl));
	$jam = date("H", strtotime($tgl));
	$menit = date("i", strtotime($tgl));
	return $tanggal.'/'.$bulan.'/'.$tahun.', '.$jam.':'.$menit;
}
function jam_tox($tgl) {
	$tanggal = date("j", strtotime($tgl));
	$bulan = date("m", strtotime($tgl));
	$tahun = date("Y", strtotime($tgl));
	$jam = date("H", strtotime($tgl));
	$menit = date("i", strtotime($tgl));
	return $jam.':'.$menit;
}
function jam_lengkap($tgl) {
	$tanggal = date("j", strtotime($tgl));
	$bulan = date("m", strtotime($tgl));
	$tahun = date("Y", strtotime($tgl));
	$jam = date("H", strtotime($tgl));
	$menit = date("i", strtotime($tgl));
	$detik = date("s", strtotime($tgl));
	return $jam.':'.$menit.':'.$detik;
}
function tgl_lengkap($tgl) {
	$tanggal = date("d", strtotime($tgl));
	$bulan = date("m", strtotime($tgl));
	$tahun = date("Y", strtotime($tgl));
	$jam = date("H", strtotime($tgl));
	$menit = date("i", strtotime($tgl));
	$detik = date("s", strtotime($tgl));
	return $tahun.'-'.$bulan.'-'.$tanggal;
}

function tgl_angka($tgl) {
	$tanggal = date("j", strtotime($tgl));
	$bulan = date("m", strtotime($tgl));
	$tahun = date("Y", strtotime($tgl));
	return $tanggal.'/'.$bulan.'/'.$tahun;
}
//tanggale bae
function tgl_tok($tgl) {
	$tanggal = date("j", strtotime($tgl));
	$bulan = date("m", strtotime($tgl));
	$tahun = date("Y", strtotime($tgl));
	return $tanggal;
}
function bln_tok($tgl) {
	$tanggal = date("j", strtotime($tgl));
	$bulan = get_bln(date("n", strtotime($tgl)));
	$tahun = date("Y", strtotime($tgl));
	return $bulan;
}
function thn_tok($tgl) {
	$tanggal = date("j", strtotime($tgl));
	$bulan = date("m", strtotime($tgl));
	$tahun = date("Y", strtotime($tgl));
	return $tahun;
}

function get_hari($hari) {
	if($hari='1'){return"Senin";}
	elseif($hari='2'){return"Selasa";}
	elseif($hari='3'){return"Rabu";}
	elseif($hari='4'){return"Kamis";}
	elseif($hari='5'){return"Jum'at";}
	elseif($hari='6'){return"Sabtu";}
	elseif($hari='7'){return"Minggu";}
}
function hari_indo($data){
					if($data=="Mon")
					{return"Senin";}
					elseif($data=="Tue")
					{return"Selasa";}
					elseif($data=="Wed")
					{return"Rabu";}
					elseif($data=="Thu")
					{return"Kamis";}
					elseif($data=="Fri")
					{return"Jum'at";}
					elseif($data=="Sat")
					{return"Sabtu";}
					else{return"Minggu";}
}
function bulan_indo($data){
	if($data=="January"){return"Januari";}
	elseif($data=="February"){return"Februari";}
	elseif($data=="March"){return"Maret";}
	elseif($data=="April"){return"April";}
	elseif($data=="May"){return"Mei";}
	elseif($data=="June"){return"Juni";}
	elseif($data=="July"){return"Juli";}
	elseif($data=="August"){return"Agustus";}
	elseif($data=="September"){return"September";}
	elseif($data=="October"){return"Oktober";}
	elseif($data=="November"){return"November";}
	elseif($data=="December"){return"Desember";}
}
function get_bulan($bln) {
	switch ($bln) {
			case '1' :
				return "Januari";
				break;
			case '2' :
				return "Februari";
				break;
			case '3' :
				return "Maret";
				break;
			case '4' :
				return "April";
				break;
			case '5' :
				return "Mei";
				break;
			case '6' :
				return "Juni";
				break;
			case '7' :
				return "Juli";
				break;
			case '8' :
				return "Agustus";
				break;
			case '9' :
				return "September";
				break;
			case '10' :
				return "Oktober";
				break;
			case '11' :
				return "November";
				break;
			case '12' :
				return "Desember";
				break;
	}
}

function get_bln($bln) {
	switch ($bln) {
			case '1' :
				return "Jan";
				break;
			case '2' :
				return "Feb";
				break;
			case '3' :
				return "Mar";
				break;
			case '4' :
				return "Apr";
				break;
			case '5' :
				return "Mei";
				break;
			case '6' :
				return "Jun";
				break;
			case '7' :
				return "Jul";
				break;
			case '8' :
				return "Ags";
				break;
			case '9' :
				return "Sep";
				break;
			case '10' :
				return "Okt";
				break;
			case '11' :
				return "Nov";
				break;
			case '12' :
				return "Des";
				break;
	}
}

//Konversi hari dari Inggris ke Indonesia
					$data=date("D");
					if($data=="Mon")
					{$hari="Senin";}
					elseif($data=="Tue")
					{$hari="Selasa";}
					elseif($data=="Wed")
					{$hari="Rabu";}
					elseif($data=="Thu")
					{$hari="Kamis";}
					elseif($data=="Fri")
					{$hari="Jum'at";}
					elseif($data=="Sat")
					{$hari="Sabtu";}
					else{$hari="Minggu";}
					
					$dd=date("D");
						
					//Konversi bulan dari Inggris ke Indonesia
					$mm=date("m");
					if($mm=='01')
					{$m="Januari";}
					elseif($mm=='02')
					{$m="Februari";}
					elseif($mm=='03')
					{$m="Maret";}
					elseif($mm=='04')
					{$m="April";}
					elseif($mm=='05')
					{$m="Mei";}
					elseif($mm=='06')
					{$m="Juni";}
					elseif($mm=='07')
					{$m="Juli";}
					elseif($mm=='08')
					{$m="Agustus";}
					elseif($mm=='09')
					{$m="September";}
					elseif($mm=='10')
					{$m="Oktober";}
					elseif($mm=='11')
					{$m="November";}
					else{$mm="Desember";}
					
					$yyyy=date('Y');
?>