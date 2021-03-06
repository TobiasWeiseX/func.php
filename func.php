<?php
//==========================================
//				func.php
//==========================================
//@Author: Tobias Weise
//@License: MIT
//supports only php7+

//the right way to force errors?
//https://secure.php.net/manual/de/errorfunc.constants.php

//Errror handling:
//http://de2.php.net/manual/en/errorfunc.configuration.php#ini.display-errors

namespace F{
    error_reporting(E_STRICT);

    use F\list_ as L;

    function fix(){
        list($f, $args) = func_get_args();
        if(is_string($f)) $f = str_replace("\t","\\t", str_replace("\n", "\\n", $f)); #escape namespace path
        return function() use(&$f, &$args){
            return call_user_func_array($f, array_merge($args, func_get_args()));
        };
    }

    #polymorphic funcs?
    function equal($a, $b){ return $a === $b; }
    function unEqual($a, $b){ return $a !== $b; }
    function isNull($x){ return is_null($x); }

    #JSON
    function toJSON($x){
        if(L\isGen($x)) $x = toArray($x);
        return json_encode($x, JSON_PRETTY_PRINT);
    }

    #utf8 strings r ideal!
    function fromJSON($s){
        if(!is_string($s)) throw new \Exception("Parse value must be string!");
        $retVal = json_decode($s, true);
        $errCode = json_last_error();
        if($errCode !== JSON_ERROR_NONE){
            switch($errCode){
                case JSON_ERROR_DEPTH:          throw new \Exception("Max Stackdepth reached");
                case JSON_ERROR_STATE_MISMATCH: throw new \Exception("State/Mode mismatch!");
                case JSON_ERROR_CTRL_CHAR:      throw new \Exception("Unexpected control char found!");
                case JSON_ERROR_SYNTAX:         throw new \Exception("Syntaxerror, invalid JSON!");
                case JSON_ERROR_UTF8:           throw new \Exception("Malformed UTF-8 char, possibly faulty decoded");
                default:                        throw new \Exception("Unknown error!");
            }
        }
        else{
            return $retVal;
        }
    }

    function echoJSON($s){ echo toJSON($s); }

    #TODO: coroutine
    function execTime($f, $args){
        $t1 = microtime(true);
        $r = call_user_func_array($f, $args);
        $t2 = microtime(true);
        return [$r, $t2-$t1];
    }

    #mimic JS-obj literals!
    #ArrayWrapperClass -> as Map keys / Set values!
    function newObj($d){
        $obj = new stdClass;
        foreach($d as $k => $v){
            $obj->{$k} = $v;
        }
        return $obj;
    }

    #TODO: constructor args : list
    #non-Object types?
    #TODO: json-compatible
    class SimpleSet{
        private $set;
        private $array; #for all the string keys.. that r not objs in php xD �.�

        public function __construct(){
            $this->set = new \SplObjectStorage();
            $this->array = [];
        }

        #TODO: does not differentiate between 1 and "1"!
        public function add($x){
            #TODO: make work with array!
            if(gettype($x)==="string" || gettype($x)==="integer") $this->array[$x] = true;
            else $this->set->attach($x);
            return $this;
        }
        public function has($x){
            #TODO: make work with array!
            if(gettype($x)==="string" || gettype($x)==="integer") return array_key_exists($x, $this->array);
            else return $this->set->contains($x);
        }
        public function delete($x){
            #TODO: make work with array!
            if(gettype($x)==="string" || gettype($x)==="integer") unset($this->array[$x]);
            else $this->set->detach($x);
            return $this;
        }
        public function size(){ return count($this->array) + $this->set->count(); }
    }

    function Set(){ return (new SimpleSet()); }

    #TODO: constructor args : dict
    #TODO: json-compatible
    class SimpleMap{
        private $map;
        private $array; #for all the string keys.. that r not objs in php xD �.�

        public function __construct(){
            $this->map = new \SplObjectStorage();
            $this->array = [];
        }

        public function set($k, $v){
            if(gettype($k)==="string") return $this->array[$k] = $v;
            else return $this->map[$k] = $v;
        }
        public function get($x){
            if(gettype($x)==="string") return $this->array[$x];
            else return $this->map[$x];
        }
        public function delete($x){
            if(gettype($x)==="string") unset($this->array[$k]);
            else unset($this->map[$x]);
            return $this;
        }
        public function has($x){
            if(gettype($x)==="string") return isset($this->array[$x]);
            else return isset($this->map[$x]);
        }
        public function size(){ return count($this->array) + $this->map->count(); }
    }

    #TODO: func_args
    function Map(){ return (new SimpleMap()); }

}

//=============
//Boolean
//=============
namespace F\bool{
    error_reporting(E_STRICT);

    function not($x){ return !$x; }
    function and_($a, $b){ return $a && $b; }
    function or_($a, $b){ return $a || $b; }
}

//=============
//Number
//=============
namespace F\number{
    error_reporting(E_STRICT);

    function smaller($a, $b){ return $a < $b; }
    function bigger($a, $b){ return $a > $b; }
    function between($min, $max, $x){ return $min < $x && $x < $max; }
    function percentOf($a, $b){ return ($b===0) ? null : ($a/($b/100.0)); }
    function inc($x){ return $x + 1; }
    function dec($x){ return $x - 1; }
    function add($a, $b){ return $a + $b; }
    function sub($a, $b){ return $a - $b; }
    function multi($a, $b){ return $a * $b; }

    function randInt($min, $max){ return rand($min, $max); }
}



//=============
//Function
//=============

namespace F\func{
    error_reporting(E_STRICT);
    use F\list_ as L;

    function iden($x){ return $x; }
    function left($a, $b){ return $a; }
    function right($a, $b){ return $b; }
    function isFunc($f){ return function_exists($f); }

    #disable Echoing!
    function silent($f){
        return function(...$args) use (&$f){
            ob_start();
            $r = $f(...$args);
            ob_end_clean();
            return $r;
        };
    }

    #TODO: make work!
    function isGenFunc($f){
        try{
            #if($f instanceof Iterator){
            return (new \ReflectionFunction($f))->isGenerator();
            #}
            #else return false;
        }
        catch(\ReflectionException $e){
            return false;
        }
        #return $f instanceof Generator;
    }

    #Todo: make variadic
    function compose(&$f, &$g){
        return function(...$args) use($f,$g){
            return $f($g(...$args));
        };
    }

    function comp(...$fs){
        $f = L\reduce(__NAMESPACE__."\compose", $fs);
        return $f;
    }

    #doesnt work:
    #$f = F\func\variadicOp("F\number\add", "F\func\iden");
    #echo $f(1,2,3);

    function variadicOp($op, $flatten){
        return function() use (&$op, &$flatten) {
            $args = func_get_args();
            return $flatten(L\reduce($op, $args));
        };
    }

    function getFuncArgs($funcName){
        #$properties = $reflector->getProperties();
        $refFunc = new ReflectionFunction($funcName);
        foreach( $refFunc->getParameters() as $param ){
            yield $param;
        }
    }

}

//=============
//Dictionary
//=============
namespace F\dict{
    error_reporting(E_STRICT);

    #null as universal NaN value?

    function isKey($k, $arr){ return array_key_exists($k, $arr); }
    function lookUp($k, $arr){ return $arr[$k]; }
    function keys($arr){ return array_keys($arr); }
    function values($arr){ return array_values($arr); }

    function unzip($arr){
        $keys = [];
        $vals = [];
        foreach($arr as $k => $v){
            $keys[] = $k;
            $vals[] = $v;
        }
        return [$keys, $vals];
    }

    function zipWith($op, $ls){
        foreach($ls as $k => $v){
            yield $op($k,$v);
        }
    }

    function toFunc($ls){
        return function($x) use($ls){
            return $ls[$x];
        };
    }

    #TODO: Set based version
    function groupBy($f, $ls){
        $d = [];
        foreach($ls as $x){
            $v = $f($x);
            if(isKey($v, $d)) $d[$v][] = $x;
            else $d[$v] = [$x];
        }
        return $d;
    }


    #transform
    function copyDictToDict($d1, $d2){
        foreach($d1 as $k => $v){
            $d2[$k] = $v;
        }
        return $d2;
    }

    function mapArray($f, $arr){
        foreach($arr as $k => $v){
            yield $f($v, $k);
        }
    }

}

//=============
//List
//=============
namespace F\list_{
    error_reporting(E_STRICT);
    #http://hackage.haskell.org/package/base-4.8.1.0/docs/Data-List.html

    function single($x){ return [$x]; }
    function isEmpty($iterable){ return length($iterable)==0; }

    function length($iterable){
        if(is_array($iterable)) return count($iterable);
        $len = 0;
        foreach($iterable as $x){
            $len += 1;
        }
        return $len;
    }

    function head($iterable){
        foreach($iterable as $x){
            return $x;
        }
        return null;
    }

    #return array_values(array_slice($ls, -1))[0];

    function last($iterable){
        if(is_array($iterable)) return $iterable[count($iterable)-1];
        $r = null;
        foreach($iterable as $x){
            $r = $x;
        }
        return $r;
    }

    #is_object

    function index($i, $ls){ return last(take($i+1, $ls)); }


    function isGen($ls){
        #gettype($anon);
        #if($f instanceof Iterator){
        return $ls instanceof Generator;
    }


    #A Generalization of Leftmost Derivations.


    #$regex = '#<a [^>]*href="(.)*"[^>]*>(.*)</a>#';



    #$anon instanceof \Closure;

    #function isGen($f){
    #    try{
    #        if($f instanceof Iterator){
    #            return (new \ReflectionFunction($f))->isGenerator();
    #        }
    #        else return false;
    #    }
    #    catch(ReflectionException $e){
    #        return false;
    #    }
    #    #return $f instanceof Generator;
    #}



    function range_($start, $end, $step=1){
        for($i=$start; $i<=$end; $i+=$step){
            yield $i;
        }
    }

    function prepend($a, $ls){
        yield $a;
        foreach($ls as $x){
            yield $x;
        }
    }

    function append($a, $ls){
        foreach($ls as $x){
            yield $x;
        }
        yield $a;
    }

    function extend($iterable, $iterable2){
        foreach($iterable as $x){
            yield $x;
        }
        foreach($iterable2 as $x){
            yield $x;
        }
    }

    function concat($ls){
        foreach($ls as $xs){
            foreach($xs as $x){
                yield $x;
            }
        }
    }

    function toArray($iterable){
        if(is_array($iterable)) return $iterable;
        $rs = [];
        foreach($iterable as $x){
            $rs[] = $x;
        }
        return $rs;
    }

    function toGen($iterable){
        foreach($iterable as $x){
            yield $x;
        }
    }

    function elem($v, $iterable){
        if(is_array($iterable)) return in_array($v, $iterable, true);
        foreach($iterable as $x){
            if($v===$x) return True;
        }
        return False;
    }

    function notElem($x, $iterable){ return !elem($x, $iterable); }

    function all($f, $iterable){
        foreach($iterable as $x){
            if(!$f($x)) return false;
        }
        return true;
    }


    /*
    function zip($a, $b){
        $g1 = toGen($a);
        $g2 = toGen($b);
        while($g1->valid() && $g2->valid()){
            yield [$g1->current(), $g2->current()];
            $g1->next();
            $g2->next();
        }
    }

    function zip3($a, $b, $c){
        $g1 = toGen($a);
        $g2 = toGen($b);
        $g3 = toGen($c);
        while($g1->valid() && $g2->valid() && $g3->valid()){
            yield [$g1->current(), $g2->current(), $g3->current()];
            $g1->next();
            $g2->next();
            $g3->next();
        }
    }
    */


    function zip(){
        $args = func_get_args();
        $gens = toArray(map(__NAMESPACE__."\\toGen", $args));
        while(all(function($gen){return $gen->valid();}, $gens)){
            yield toArray(map(function($x){return $x->current();}, $gens));
            foreach($gens as $g){
                $g->next();
            }
        }
    }

    function zipWith($op, $a, $b){
        $g1 = toGen($a);
        $g2 = toGen($b);
        while($g1->valid() && $g2->valid()){
            yield $op($g1->current(), $g2->current());
            $g1->next();
            $g2->next();
        }
    }

    function reverse($iterable){
        return reduce(function($a, $b){
            return extend($b, $a);
        }, map(__NAMESPACE__."\\single", $iterable));
    }

    function reverse2($iterable){ return array_reverse(toArray($iterable)); }

    function noDoubles($iterable){
        $known = [];
        foreach($iterable as $x){
            #if(!hasElem($known, $x)) yield $x;
            if(!elem($x, $known)) yield $x;
        }
    }

    #flatten array

    //TODO: make variadic
    function cartProd($ls, $xs){
        foreach($ls as $x){
            foreach($xs as $y){
                yield [$x, $y];
            }
        }
    }

    function take($n, $ls){
        foreach($ls as $x){
            if($n==0) break;
            yield $x;
            $n -= 1;
        }
    }

    function drop($n, $ls){
        foreach($ls as $x){
            if($n<=0) yield $x;
            $n -= 1;
        }
    }

    function takeWhile($p, $iterable){
        foreach($iterable as $x){
            if($p($x)) yield $x;
            else break;
        }
    }

    function dropWhile($p, $iterable){
        foreach($iterable as $x){
            if(!$p($x)) yield $x;
            else break;
        }
    }

    function tail($iterable){
        $isFirst = true;
        foreach($iterable as $x){
            if($isFirst) $isFirst = false;
            else yield $x;
        }
    }

    function init($iterable){
        $i = length($iterable);
        foreach($iterable as $x){
            if($i < 2) break;
            yield $x;
            $i -= 1;
        }
    }

    function filter($f, $iterable){
        foreach($iterable as $x){
            if($f($x)) yield $x;
        }
    }

    function map($f, $iterable){
        foreach($iterable as $x){
            yield $f($x);
        }
    }

    #folds
    function reduce($op, $iterable){
        #if(is_string($op)) $op = str_replace("\t","\\t", str_replace("\n", "\\n", $op));
        $acc = head($iterable);
        foreach(tail($iterable) as $x){
            $acc = $op($acc, $x);
        }
        return $acc;
    }

    #use a queue genFunc for non copying!


    #use bidirectional gen + predicate to decide whats put back in the queue?
    function queue(){
        while(true){
            foreach($iterable as $x){
                yield $x;
            }
        }
    }

    #stack genFunc?

    function cycle($iterable){ #todo: if somethings a gen : copy & exec copy
        while(true){
            foreach($iterable as $x){
                #if(isGen($x)) $x->rewind();
                #yield copy($x)?
                yield $x;
            }
        }
    }


    #repeat

    #replicate

}

//=============
//String
//=============

#TODO: use mb/multibyte string funcs only!

namespace F\string{
    error_reporting(E_STRICT);
    use F\list_ as L;

    function slice($i, $j, $s){ return mb_substr($s, $i, $j-$i+1); }
    function add($a, $b){ return $a.$b; }
    function upper($s){ return mb_strtoupper($s, 'utf8'); }
    function lower($s){ return mb_strtolower($s, 'utf8'); }
    function length($s){ return mb_strlen($s, 'utf8'); }


    function head($s){ return $s[0]; }
    function tail($s){ return slice(1, length($s)-1, $s); }

    #untested
    function uncons($s){
        return [head($s), tail($s)];
    }

    //https://stackoverflow.com/questions/3786003/str-replace-on-multibyte-strings-dangerous

    //http://iamseanmurphy.com/mb-str-replace-the-missing-php-function/

    /*
    function mb_replace($search, $replace, $subject, &$count=0) {
        if (!is_array($search) && is_array($replace)) {
            return false;
        }
        if (is_array($subject)) {
            // call mb_replace for each single string in $subject
            foreach ($subject as &$string) {
                $string = &mb_replace($search, $replace, $string, $c);
                $count += $c;
            }
        } elseif (is_array($search)) {
            if (!is_array($replace)) {
                foreach ($search as &$string) {
                    $subject = mb_replace($string, $replace, $subject, $c);
                    $count += $c;
                }
            } else {
                $n = max(count($search), count($replace));
                while ($n--) {
                    $subject = mb_replace(current($search), current($replace), $subject, $c);
                    $count += $c;
                    next($search);
                    next($replace);
                }
            }
        } else {
            $parts = mb_split(preg_quote($search), $subject);
            $count = count($parts)-1;
            $subject = implode($replace, $parts);
        }
        return $subject;
    }
    */


#if (!function_exists('mb_str_replace')) {
	function mb_str_replace($search, $replace, $subject, &$count = 0) {
		if (!is_array($subject)) {
			// Normalize $search and $replace so they are both arrays of the same length
			$searches = is_array($search) ? array_values($search) : array($search);
			$replacements = is_array($replace) ? array_values($replace) : array($replace);
			$replacements = array_pad($replacements, count($searches), '');
			foreach ($searches as $key => $search) {
				$parts = mb_split(preg_quote($search), $subject);
				$count += count($parts) - 1;
				$subject = implode($replacements[$key], $parts);
			}
		} else {
			// Call mb_str_replace for each subject in array, recursively
			foreach ($subject as $key => $value) {
				$subject[$key] = mb_str_replace($search, $replace, $value, $count);
			}
		}
		return $subject;
	}
#}


    function replace($old, $new, $s){
        return mb_str_replace($old, $new, $s);
    }


    function charList($s){ return preg_split('//u', $s, null, PREG_SPLIT_NO_EMPTY); }

    #TODO: rewrite
    function fill($n, $s){
        $acc = "";
        for($i=0; $i<$n; $i++){
            $acc .= $s;
        }
        return $acc;
    }

    function hasSubstr($s, $ss){ return strpos($s, $ss) !== false; }

    #alternative haskell name? intersperse?
    function sepBy($del, $s){ return implode($del, charList($s)); }

    function charPos($s, $c){
        $rs = [];
        $len = length($s);
        for($i = 0; $i < $len; $i++){
            if($s[$i] === $c) $rs[] = $i;
        }
        return $rs;
    }

    #cause implode doesnt know generators
    function join_($del, $ls){
        return L\reduce(function($a, $b)use($del){return $a.$del.$b;}, $ls);
    }


    #split?

    #text module?
    #define newline, space, indentation on text basis:
    #<br /> not \n, 4x" " instead of \t

    #TODO create genericFunc that produces paires like lines & unlines

    #Text
    function lines($s){ return explode("\n", $s); }
    function unlines($lines){ return join_("\n", $lines); }


    function replicateStr($n, $s){
        $r = "";
        for($i=0; $i<$n; $i++){
            $r += $s;
        }
        return $r;
    }

    function fillInFront($char, $len, $x){
        $s = $x."";
        $l = length($s);
        return ($l<$len ? replicateStr($len-$l, $char) : "").$s;
    }

}

//=============
//Regex
//=============
namespace F\regex{
    error_reporting(E_STRICT);


    #$regex = '#<a [^>]*href="(.)*"[^>]*>(.*)</a>#';
    #create predicate from regex?

    function findAll($rgx, $txt){
        $rs = [];
        preg_match_all("#".$rgx."#", $txt, $rs);
        return $rs[0];
    }

}

//=============
//Time
//=============

namespace F\time{
    error_reporting(E_STRICT);
    use F\string as S;

    function yearShort($x){
        return S\slice(2,4, $x); //2016 -> 16
    }

    function weekNumber(){
        $ddate = date('Y-m-d H:i:s');
        #$ddate = "2012-10-18";
        $date = new \DateTime($ddate);
        $week = $date->format("W");
        return $week;
    }

}

//=============
//IO
//=============
namespace F\io{
    error_reporting(E_STRICT);

    ini_set('user_agent', 'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3');

    function readUrl($url){ return file_get_contents($url); }

    #TODO: error handling?
    function download($url, $path){
        $c = file_get_contents($url);
        file_put_contents($path, $c);
    }


}

namespace F\io\file{
    error_reporting(E_STRICT);

    #0777 means ?

    function showPerm($path){ return decoct(fileperms($path) & 0777); }
    function hasPerm($path){ return 0755 === (fileperms($path) & 0777); }
    function exists($p){ return file_exists($p); }

    #TODO: rename to open?
    function getFileHandle($path, $mode){
        $f = fopen($path, $mode);
        if($f) return $f;
        else throw new \Exception("Unable to open file! (".$path.") in ".$mode."-mode !");
    }

    function write($path, $c){
        $f = getFileHandle($path, "w");
        fwrite($f, $c);
        fclose($f);
    }

    function writeBin($path, $c){
        $f = getFileHandle($path, "wb");
        fwrite($f, $c);
        fclose($f);
    }

    function append($path, $c){
        $f = getFileHandle($path, "a");
        fwrite($f, $c);
        fclose($f);
    }

    function appendLn($path, $c){
        $f = getFileHandle($path, "a");
        fwrite($f, $c."\n");
        fclose($f);
    }

    #generators for file reading?
    #and parsing?
    #or higher order funcs?

    function readBin($path){
        #$h = fopen($path, "rb");
        $h = getFileHandle($path, "rb");
        $c = stream_get_contents($h);
        fclose($h);
        return $c;
    }


    #error case handling?
    function read($path){
        #$h = fopen($path, 'r');

        #if(!$h) return null;
        #$c = fgets($h);

        #fclose($h);
        #return $c;

        return file_get_contents($path);
    }

    function createIfNotExists($path){
        if(!exists($path)){
            write($path,"");
            return exists($path);
        }
        return true;
    }
}

namespace F\io\dir{
    error_reporting(E_STRICT);

    function cwd(){ return getcwd(); }

    //TODO: gen
    function listAll($path){
	    $ls = [];
	    if($handle = opendir($path)){
		    while(false !== ($entry=readdir($handle))) {
			    $ls[] = $entry;
		    }
		    closedir($handle);
	    }
	    return $ls;
    }

    function createIfNotExists($path){
        if(!is_dir($path)){
            mkdir($path);
            return is_dir($path);
        }
        return true;
    }

    function preg_ls ($path=".", $rec=false, $pat="/.*/") {
        $pat = preg_replace("|(/.*/[^S]*)|s", "\\1S", $pat);
        while(substr($path,-1,1)=="/"){
            $path = substr($path, 0, -1);
        }
        if(!is_dir($path)) $path = dirname($path);
        #if($rec!==true) $rec = false;
        $d = dir($path);
        $ret = [];
        while(false!==($e=$d->read())){
            if(($e==".")||($e=="..")) continue;
            if($rec && is_dir($path."/".$e)) {
                $ret = array_merge($ret, preg_ls($path."/".$e,$rec,$pat));
                continue;
            }
            if(!preg_match($pat,$e)) continue;
            $ret[] = $path."/".$e;
        }
        return $ret;
    }


}

namespace F\io\path{
    error_reporting(E_STRICT);

    use F\list_ as L;

    function ext($path){
        return strtolower(L\last(explode(".", $path)));
    }

}






//=============
//ini
//=============
namespace F\ini{
    error_reporting(E_STRICT);
    function parseFile($path){ return parse_ini_file($path, true, INI_SCANNER_TYPED); }
}


//hashing
//Sha1 md5

//=============
//DOM
//=============

namespace F\dom{
    error_reporting(E_STRICT);

    function getInnerHTML($node){
         $body = $node->ownerDocument->documentElement->firstChild->firstChild;
         $doc = new DOMDocument();
         $doc->appendChild($doc->importNode($body,true));
         return $doc->saveHTML();
    }

    function traverseDOM($ele, $f){
        if($ele->nodeType === XML_ELEMENT_NODE){
            $f($ele);
            if($ele->hasChildNodes()){
                foreach($ele->childNodes as $ele2){
                    traverseDOM($ele2, $f);
                }
            }
        }
    }

    function getElementsByClassName($root, $clsName){
        $rs = [];
        traverseDOM($root, function($ele) use (&$rs, $clsName){
            if($ele->hasAttribute("class")){
                $parts = explode(" ", $ele->getAttribute("class"));
                if(in_Array($clsName, $parts)) $rs[] = $ele;
            }
        });
        return $rs;
    }

}





