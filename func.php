<?php
//=========================================================================
//						Functional Programming
//=========================================================================
//Author: Tobias Weise
//License: BSD3
//https://opensource.org/licenses/BSD-3-Clause

namespace F{
    error_reporting(-1);
    #error_reporting(E_STRICT);
    use F\list_ as L;
    
    #session_unset(); // remove all session variables
    #session_destroy();  // destroy the session

    #todo: rewrite
    function function_alias($original, $alias){
        $args = func_get_args();
        assert('count($args) == 2', 'function_alias(): requires exactly two arguments');
        assert('is_string($original) && is_string($alias)', 'function_alias(): requires string arguments');
        // valid function name - http://php.net/manual/en/functions.user-defined.php
        assert('preg_match(\'/^[a-zA-Z_\x7f-\xff][\\\\\\\\a-zA-Z0-9_\x7f-\xff]*$/\', $original) > 0',
            "function_alias(): '$original' is not a valid function name");
        assert('preg_match(\'/^[a-zA-Z_\x7f-\xff][\\\\\\\\a-zA-Z0-9_\x7f-\xff]*$/\', $alias) > 0',
            "function_alias(): '$alias' is not a valid function name");
        $aliasNamespace = substr($alias, 0, strrpos($alias, '\\') !== false ? strrpos($alias, '\\') : 0);
        $aliasName = substr($alias, strrpos($alias, '\\') !== false ? strrpos($alias, '\\') + 1 : 0);
        $serializedOriginal = var_export($original, true);
        eval("
            namespace $aliasNamespace {
                function $aliasName () {
                    return call_user_func_array($serializedOriginal, func_get_args());
                }
            }
        ");
    }

    #todo: rewrite
    function import_namespace($source, $destination){
        $args = func_get_args();
        assert('count($args) == 2', 'import_namespace(): requires exactly two arguments');
        assert('is_string($source) && is_string($destination)', 'import_namespace(): requires string arguments');

        //valid function name - http://php.net/manual/en/functions.user-defined.php
        assert('preg_match(\'/^([a-zA-Z_\x7f-\xff][\\\\\\\\a-zA-Z0-9_\x7f-\xff]*)?$/\', $source) > 0',
            "import_namespace(): '$destination' is not a valid namespace name");
        assert('preg_match(\'/^([a-zA-Z_\x7f-\xff][\\\\\\\\a-zA-Z0-9_\x7f-\xff]*)?$/\', $destination) > 0',
            "import_namespace(): '$source' is not a valid namespace name");

        foreach(get_declared_classes() as $class)
            if(strpos($class, $source . '\\') === 0)
                class_alias($class, $destination . ($destination ? '\\' : '') . substr($class, strlen($source . '\\')));

        $functions = get_defined_functions();
        foreach(array_merge($functions['internal'], $functions['user']) as $function)
            if (strpos($function, $source . '\\') === 0)
                function_alias($function, $destination . ($destination ? '\\' : '') . substr($function, strlen($source . '\\')));
    }

    function use_namespace($src){ import_namespace($src, __NAMESPACE__); }
    
    #fix replaces the auto-currying-mechanism!
    
    #if(version_compare(PHP_VERSION, '5.6.0', '>=')){
    #    function fix($f, ...$args){
    #        return function(...$args2) use(&$f, &$args){
    #            return $f(...$args, ...$args2);
    #        };
    #    }        
    #}
    #else{


    #todo:
    #$inc = F\fix("add", 1);
    #move to F
    function fix(){
        list($f, $args) = func_get_args();
        if(is_string($f)) $f = str_replace("\t","\\t", str_replace("\n", "\\n", $f)); #escape namespace path
        return function() use(&$f, &$args){
            return call_user_func_array($f, array_merge($args, func_get_args()));
        };
    }        
    #}    
    
    
    
    
    
    #polymorphic funcs?
    function equal($a, $b){ return $a === $b; }
    function unEqual($a, $b){ return $a !== $b; }
    function isNull($x){ return is_null($x); }

    function toJSON($x){
        if(L\isGen($x)) $x = toArray($x);
        return json_encode($x, JSON_PRETTY_PRINT);
    }

    function echoJSON($x){ echo toJSON($x); }
    
    #todo: coroutine
    function execTime($f, $args){
        $start = microtime(true);
        $r = call_user_func_array($f, $args);
        $end = microtime(true);
        return [$r, $end-$start];
    }

    #$o1 = new stdClass;
    #$o1->foo = 'bar';


    /*
    
    public function __call($method, $arguments) {
        $arguments = array_merge(array("stdObject" => $this), $arguments); // Note: method argument 0 will always referred to the main class ($this).
        if (isset($this->{$method}) && is_callable($this->{$method})) {
            return call_user_func_array($this->{$method}, $arguments);
        } else {
            throw new Exception("Fatal error: Call to undefined method stdObject::{$method}()");
        }
    }
    
    */

    
    #$this->{$property} = $argument;
    #!is_callable($f)
    #memory_get_usage()
    #is_array

    
    #mimic JS-obj literals!
    #function createObj(){

    #}
    
    #ArrayWrapperClass -> as Map keys / Set values!
    
    
    #todo: constructor args : list
    #non-Object types?
    #todo: json-compatible
    class SimpleSet{
        private $set;
        private $array; #for all the string keys.. that r not objs in php xD �.�

        public function __construct(){
            $this->set = new \SplObjectStorage();
            $this->array = [];
        }
        
        #todo: does not differentiate between 1 and "1"!
        public function add($x){
            #todo: make work with array!
            if(gettype($x)==="string" || gettype($x)==="integer") $this->array[$x] = true;
            else $this->set->attach($x);
            return $this;
        }
        public function has($x){
            #todo: make work with array!
            if(gettype($x)==="string" || gettype($x)==="integer") return array_key_exists($x, $this->array);
            else return $this->set->contains($x);
        }
        public function delete($x){
            #todo: make work with array!
            if(gettype($x)==="string" || gettype($x)==="integer") unset($this->array[$x]);
            else $this->set->detach($x);
            return $this;
        }
        public function size(){ return count($this->array) + $this->set->count(); }
    }

    function Set(){ return (new SimpleSet()); }
    
    #todo: constructor args : dict
    #todo: json-compatible
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

    #todo: func_args    
    function Map(){ return (new SimpleMap()); }

}

//=============
//Boolean
//=============

namespace F\bool{
    error_reporting(-1);
    #error_reporting(E_STRICT);


    function not($x){ return !$x; }
    function and_($a, $b){ return $a && $b; }
    function or_($a, $b){ return $a || $b; }
}

//=============
//Number
//=============

namespace F\number{
    error_reporting(-1);
    #error_reporting(E_STRICT);

    function smaller($a, $b){ return $a < $b; }
    function bigger($a, $b){ return $a > $b; }
    function between($min, $max, $x){ return $min < $x && $x < $max; }
    function percentOf($a, $b){ return ($b===0) ? null : ($a/($b/100.0)); }
    function inc($x){ return $x + 1; }
    function dec($x){ return $x - 1; }    
    function add($a, $b){ return $a + $b; }
    function sub($a, $b){ return $a - $b; }
    function multi($a, $b){ return $a * $b; }
}

//=============
//Tuple
//=============

namespace F\tuple{
    #http://hackage.haskell.org/package/base-4.8.1.0/docs/Data-Tuple.html
    error_reporting(-1);
    #error_reporting(E_STRICT);

    function fst($iterable){
        list($a, $b) = $iterable;
        return $a;
    }

    function snd($iterable){
        list($a, $b) = $iterable;
        return $b;
    }

    function swap($iterable){
        list($a, $b) = $iterable;
        return [$b, $a];
    }

}

//=============
//Function
//=============

namespace F\func{
    error_reporting(-1);
    #error_reporting(E_STRICT);
    use F\list_ as L;

    function iden($x){ return $x; }
    function left($a, $b){ return $a; }
    function right($a, $b){ return $b; }
    function isFunc($f){ return function_exists($f); }

    #todo: make work!
    function isGenFunc($f){
        try{
            #if($f instanceof Iterator){
            return (new \ReflectionFunction($f))->isGenerator();
            #}
            #else return false;
        }
        catch(ReflectionException $e){
            return false;
        }
        #return $f instanceof Generator;
    }
    
    #todo: create comp
    function compose(&$f, &$g){
        return function() use($f,$g){
            return $f(call_user_func_array($g, func_get_args()));
        };
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

    function comp(){
        $fs = func_get_args();
        $f = L\reduce(__NAMESPACE__."\compose", $fs);
        return $f;
    }


    function getFuncArgs($funcName){
        #$properties = $reflector->getProperties();
        $refFunc = new ReflectionFunction($funcName);
        foreach( $refFunc->getParameters() as $param ){
            yield $param;
        }
    }
    
    /*
    #Y-combinator
    function fix( $func ){
        return function() use ( $func ){
            $args = func_get_args();
            array_unshift( $args, fix($func) );
            return call_user_func_array( $func, $args );
        };
    }

    $factorial = function( $func, $n ) {
        if ( $n == 1 ) return 1;
        return $func( $n - 1 ) * $n;
    };
    $factorial = fix( $factorial );

    print $factorial( 5 );

    */    
    
    #MEMOIZATION DECO
    
    /*
    function get_static_rand() {
        static $rand = null;
        if(is_null($rand)) {
            $rand = rand();
        }
        return $rand;
    }
    echo get_static_rand(); // 985873932

    echo "<br><br>";

    echo get_static_rand(); // 985873932
    */    
    
    
    /*
    function fac($n){
        static $mem = null;
        if($n === 0){
            #if(is_null($mem)) $mem
            return 1;
        }
        return $n * fac($n - 1);
    }
    */
    
}

//=============
//Dictionary
//=============

namespace F\dict{
    error_reporting(-1);
    #error_reporting(E_STRICT);

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

    #todo: Set based version
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


#decompose funcs <-> comp


//=============
//List
//=============
namespace F\list_{
    error_reporting(-1);
    #error_reporting(E_STRICT);
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
 
    function last($iterable){
        if(is_array($iterable)) return $iterable[count($iterable)-1];
        $current = null;
        foreach($iterable as $x){
            $current = $x;
        }
        return $current;
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
        $gens = toArray(map("toGen", $args));
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
        }, map("single", $iterable));  
    }    

    function reverse2($iterable){ return array_reverse(toArray($iterable)); }      

    function noDoubles($iterable){
        $known = [];
        foreach($iterable as $x){
            if(!hasElem($known, $x)) yield $x;
        }
    }
 
    //todo: make variadic
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

}

//=============
//String
//=============
namespace F\string{
    error_reporting(-1);
    #error_reporting(E_STRICT);

    use F\list_ as L;

    function slice($s, $i, $j){ return substr($s, $i, $j); }
    function add($a, $b){ return $a.$b; }
    function charList($s){ return preg_split('//u', $s, null, PREG_SPLIT_NO_EMPTY); }
    function upper($s){ return mb_strtoupper($s, 'UTF-8'); }
    function lower($s){ return mb_strtolower($s, 'UTF-8'); }
    function length($s){ return mb_strlen($s, 'utf8'); }

    function fill($n, $s){
        $acc = "";
        for($i=0; $i<$n; $i++){
            $acc .= $s;
        }
        return $acc;
    }
 
    function letters($s){ return L\noDoubles(charList($s)); }
    function sepBy($del, $s){ return implode($del, charList($s)); } 
    
    function unmask($s, $visibleLetters, $cover="_"){
        $retS = "";
        foreach(charList($s) as $c){
            $retS .= L\hasElem($visibleLetters, $c) ? $c : $cover;
        }
        return $retS;
    }   

    function hasSubstr($s, $ss){ return strpos($s, $ss) !== false; }

    function charPos($s, $c){
        $rs = [];
        $len = length($s);
        for($i = 0; $i < $len; $i++){ 
            if($s[$i] === $c) $rs[] = $i;
        }    
        return $rs;
    }

    function join_($del, $ls){#cause implode doesnt know generators
        return L\reduce(function($a, $b)use($del){return $a.$del.$b;}, $ls);
    }   

}

//=============
//Regex
//=============
namespace F\regex{
    error_reporting(-1);
    #error_reporting(E_STRICT);

    function findAll($rgx, $txt){
        $rs = [];
        preg_match_all("#".$rgx."#", $txt, $rs);
        return $rs[0];
    }

}

//=============
//IO
//=============
namespace F\io{
    error_reporting(-1);
    #error_reporting(E_STRICT);

    function readUrl($url){ return file_get_contents($url); }
}

namespace F\io\file{
    error_reporting(-1);
    #error_reporting(E_STRICT);

    function showPerm($path){ return decoct(fileperms($path) & 0777); }
    function hasPerm($path){ return 0755 === (fileperms($path) & 0777); }
    function exists($p){ return file_exists($p); }

    function write($path, $c){
        $f = fopen($path, 'w');
        fwrite($f, $c);
        fclose($f);
    }

    function writeBin($path, $c){
        $f = fopen($path, 'wb');
        fwrite($f, $c);
        fclose($f);
    }

    function append($path, $c){
        $f = fopen($path, 'a');
        fwrite($f, $c);
        fclose($f);
    }

    function appendLn($path, $c){
        $f = fopen($path, 'a');
        fwrite($f, $c."\n");
        fclose($f);
    }

    function read($path){
        $h = fopen($path, 'r');
        $c = fgets($h);
        fclose($h);
        return $c;
    }

    function readBin($path){
        $h = fopen($path, 'rb');
        $c = fgets($h);
        fclose($h);
        return $c;
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
    error_reporting(-1);
    #error_reporting(E_STRICT);

    function cwd(){ return getcwd(); }

    //todo: gen
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
    error_reporting(-1);
    #error_reporting(E_STRICT);

    use F\list_ as L;

    function ext($path){ return strtolower(L\last(explode(".", $path))); }

}

//=============
//mysql
//=============
#only use standard sql? rename to sql?
namespace F\mysql{
    error_reporting(-1);
    #error_reporting(E_STRICT);

    //echo dirname(__FILE__);
    #echo __FILE__;
    #@register_shutdown_function(mysql_close);

    function query($con, $sql){ return mysqli_query($con, $sql); }

    #todo: error handling
    function connect($host, $user, $pwd, $db){
        $con = mysqli_connect($host, $user, $pwd, $db);
        if($con === false){
            #die("Connection failed: " . mysqli_connect_error());
            throw new Exception("Connection failed: " . mysqli_connect_error());
        }
        else return $con;
    }

}

namespace F\mysql\types{
    error_reporting(-1);
    #error_reporting(E_STRICT);
}

namespace F\mysql\table{
    error_reporting(-1);
    #error_reporting(E_STRICT);

    use F\mysql as M;
    use F\list_ as L;
    use F\dict as D;
    use F\string as S;


    function create($dbH, $name, $fields, $uniques, $primarykeys){
        foreach($uniques as $v){
            if(!D\isKey($v, $fields)){
                die("MYSQL-error: '".$v."' is not a valid field name!");
            }
        }
        $s = "CREATE TABLE IF NOT EXISTS ".$name." (
                    ".S\join_(", ", D\zipAssoc(function($a,$b){return $a." ".$b;}, $fields)).",
                    PRIMARY KEY (".S\join_(",",$primarykeys)."),
                    UNIQUE(".S\join_(",",$uniques).")
                );";
        $result = M\query($dbH, $s);
        if(!is_bool($result)){
            echo mysqli_errno($result) . ": " . mysql_error($result). "\n";
        }
    }


    function createSimple($dbH, $name, $fields, $uniques){
        foreach($uniques as $v){
            if(!D\isKey($v, $fields)){
                die("MYSQL-error: '".$v."' is not a valid field name!");
            }
        }
        $s = "CREATE TABLE IF NOT EXISTS ".$name." (
                    id int(6) NOT NULL auto_increment,
                    ".join_(", ", D\zipAssoc(function($a,$b){return $a." ".$b;}, $fields)).",
                    PRIMARY KEY (id),
                    UNIQUE(".join_(",",$uniques).")
                );";
        $result = M\query($dbH, $s);
        if(!is_bool($result)){
            echo mysql_errno($result).": ".mysql_error($result)."\n";
        }
    }


    function insert($con, $tableName, $dict){
        $fields = implode(',', D\keys($dict));
        $values = substr(json_encode(D\values($dict)), 1, -1);
        return M\query($con, "INSERT INTO ".$tableName."(".$fields.") VALUES (".$values.")");
    }

    function drop($con, $name){ return M\query($con, "DROP TABLE ".$name.";"); }



    #function varchar($n){
    #    if($n > 767){
    #        die("varchar limit is 767!");
    #    }
    #    return "varchar(".$n.")";
    #}

}

namespace F\mysql\table\select{
    error_reporting(-1);
    #error_reporting(E_STRICT);

    //if(mysqli_num_rows($r) > 0){
    
    function single($con, $tableName, $fields=["*"], $cond="true"){
        $r = mysqli_query($con, "SELECT ".implode(",", $fields)." FROM ".$tableName." WHERE ".$cond." ;");
        if(!is_bool($r)){
            while($row = mysqli_fetch_assoc($r)){
                yield $row;
            }
        }
    }

    #todo: 1 array for all the join rules instead of tableName+joins
    function select($con, $tableName, $joins, $fields, $condition){
        #JOIN film_actor ON (film.film_id = film_actor.film_id)
        # tname => "cond"
        #todo: rewrite no foreach, S\concat
        $s = "";
        foreach($joins as $tName => $cond){
            $s .= " JOIN ".$tName." ON (".$cond.") ";
        }
        $r = mysqli_query($con, "SELECT ".implode(",", $fields)." ".$s." FROM ".$tableName." WHERE ".$condition." ;");
        if(!is_bool($r)){
            while($row = mysqli_fetch_assoc($r)){
                yield $row;
            }
        }
    }

}

//=============
//ini
//=============
namespace F\ini{
    error_reporting(-1);
    #error_reporting(E_STRICT);
    
    
    if(version_compare(PHP_VERSION, '5.6.0', '>=')) {
        function parseFile($path){ return parse_ini_file($path, true, INI_SCANNER_TYPED); }
    }
    else{
        function parseFile($path){ return parse_ini_file($path, true); }
    }

}


//=============
//image
//=============
namespace F\image{
    error_reporting(-1);
    #error_reporting(E_STRICT);
    #Supports: jpg png
    #sudo apt-get install php5-gd
    #sudo apt-get install php5-imagick

    use F\list_ as L;

    function resizeImage($path, $new_path, $width, $height){
        #echo $path;
	    #$ext = path_to_ext($path);
        $ext = strtolower(L\last(explode(".", $path)));
        $filename = $path;
        list($width_orig, $height_orig) = getimagesize($filename);
        $ratio_orig = $width_orig / $height_orig;
        if($width / $height > $ratio_orig) $width = $height * $ratio_orig;
        else $height = $width / $ratio_orig;
        $image_p = imagecreatetruecolor($width, $height);
	    switch($ext){
		    case "jpg":
		    case "jpeg":
			    $image = imagecreatefromjpeg($filename);
			    break;
		    case "png":
			    $image = imagecreatefrompng($filename);
			    break;
		    case "gif":
			    $image = imagecreatefromgif($filename);
			    break;

		    case "bmp":
			    $image = imagecreatefromwbmp($filename);
			    break;

	    }
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
	    switch($ext){
		    case "jpg":
		    case "jpeg":
			    imagejpeg($image_p, $new_path, 100);
			    break;
		    case "png":
			    imagepng($image_p, $new_path);
			    break;
		    case "gif":
			    imagegif($image_p, $new_path);
			    break;
		    case "bmp":
                imagewbmp($image_p, $new_path);
			    break;
	    }
        imagedestroy($image);
        imagedestroy($image_p);
    }


    function resizeByHeight($path, $new_path, $height){
        $ext = strtolower(L\last(explode(".", $path)));
        $filename = $path;
        list($width_orig, $height_orig) = getimagesize($filename);
	    $width = ($height/$height_orig) * $width_orig;
        resizeImage($path, $new_path, $width, $height);
    }

}

//=============
//pdf
//=============
namespace F\pdf{
    #needs imagick
    error_reporting(-1);
    #error_reporting(E_STRICT);

    function numberPages($pdfname) {
        $pdftext = file_get_contents($pdfname);
        $num = preg_match_all("/\/Page\W/", $pdftext, $dummy);
        return $num;
    }

}

namespace F\pdf\thumbnail{
    error_reporting(-1);
    #error_reporting(E_STRICT);

    function create($srcPath, $w, $h, $path){
        $img = new \Imagick($srcPath.'[0]');
        $img->setImageFormat('jpg');
        $img->thumbnailImage($w, $h, true, true);
        file_put_contents($path, $img);
    }

    function createByHeight($srcPath, $h, $targetPath){
        $ratio = 210.0/297.0;
        $w = $ratio * $h;
        create($srcPath, $w, $h, $targetPath);
    }

}

//for specialized pages to set up
namespace F\prog{
    error_reporting(-1);
    #error_reporting(E_STRICT);
    use F\dict as D;
    
    //onExit
    //register_shutdown_function

    //rename to json microservice?
    #todo: failcase -> one fails al fail or ignore defect json?
    function microService($f){
        $rs = D\mapArray("json_decode", $_POST);
        echo json_encode(call_user_func($f, $rs), JSON_PRETTY_PRINT);
        exit(0);
    }

    //todo:
    //templating skeletons for different formats?
    #rss
    #svg?
    #css
}
