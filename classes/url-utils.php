<?php

class pmlnr_url {

	public function __construct () {
	}

	public static function sxg_to_num( $s ) {
		$n = 0;
		$j = strlen($s);
		for ($i=0;$i<$j;$i++) { // iterate from first to last char of $s
			$c = ord($s[$i]); //	put current ASCII of char into $c
			if ($c>=48 && $c<=57) { $c=$c-48; }
			else if ($c>=65 && $c<=72) { $c-=55; }
			else if ($c==73 || $c==108) { $c=1; } // typo capital I, lowercase l to 1
			else if ($c>=74 && $c<=78) { $c-=56; }
			else if ($c==79) { $c=0; } // error correct typo capital O to 0
			else if ($c>=80 && $c<=90) { $c-=57; }
			else if ($c==95) { $c=34; } // underscore
			else if ($c>=97 && $c<=107) { $c-=62; }
			else if ($c>=109 && $c<=122) { $c-=63; }
			else { $c = 0; } // treat all other noise as 0
			$n = 60*$n + $c;
		}
		return $n;
	}

	public static function num_to_sxg( $n ) {
		$s = "";
		$m = "0123456789ABCDEFGHJKLMNPQRSTUVWXYZ_abcdefghijkmnopqrstuvwxyz";
		if ($n===null || $n===0) { return 0; }
		while ($n>0) {
			$d = $n % 60;
			$s = $m[$d] . $s;
			$n = ($n-$d)/60;
		}
		return $s;
	}
}
