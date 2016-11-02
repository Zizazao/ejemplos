<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

use App\Http\Requests;
use Validator;

use \Illuminate\Database\Eloquent\Collection;
use App\Curso;
use App\Categoria;
use App\Bloque;
use App\Capitulo;
use Illuminate\Support\Facades\Input;
use Intervention\Image\ImageManager;


class cursosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

	/**
	 * 	Enseña la vista con el formulario para crear el Curso.
	 */
    public function crear()
    {

    	$categorias = Categoria::lists('name', 'id');
       
        return view('crear-curso', compact('categorias'));
    }

    /**
	 * 	Guarda el curso y redirige al dashboard
	 *	con el listado de cursos que haya creado el usuario.
	 */

    public function guardar(Request $request)
    {
     

        $error = cursosController::ValidacionF($request);

        if($error['error'] == 0){

            $userId = \Auth::user()->id;
            $fileImg = Input::file('imagen_portada');
            $fileVideo = Input::file('video_promo');

            if($fileImg){

                $fileImg->move(public_path().'/cursoimages/', $imgName = $userId. '-'. $fileImg->getClientOriginalName());
            }

            if($fileVideo){

                $fileVideo->move(public_path().'/cursovideos/', $videoName = $userId. '-'. $fileVideo->getClientOriginalName());

            }

            $curso = \Auth::user()->curso()->create($request->all());

            if($request->input('titulo')!=''){
                $curso->titulo = $request->input('titulo');
            }

            // Añado variable $curso_id para relaccionar los elementos de los capitulos
            $curso_id = $curso->id;

            //Añado isset
            if(isset($imgName))
            {
                $curso->imagen_portada = $imgName;
                $curso->save();
                $manager = new ImageManager;
                $public = public_path();
                $publicPortada = $public.'/'.'cursoimages/';
                $manager->make($publicPortada. $imgName)->resize(350, 190)->save($publicPortada. 'resized-'.$imgName);
            }

            //Añado isset
            if(isset($videoName))
            {
                $curso->video_promo = $videoName;
                $curso->save();
            }

            $curso->subcategoria = $request->input('subcategoria');
            $curso->save();
            $curso->categoria = $request->input('categorias');
            $curso->save();

            $curso->categorias()->attach($request->input('categorias'));
            
            //bloque
            foreach ($request->all() as $key => $value) {

                $filtroBloque= substr($key, 0,6);

                if ($filtroBloque === 'bloque'){

                    $iBloque = new Bloque;

                    $params = explode('|', $key);               
                    $numero = $params[1];

                    $nombre = $value;

                    $iBloque->name = $nombre;
                    $iBloque->numero = $numero;
                    $iBloque->curso_id = $curso_id;

                    $iBloque->save();
                }
            }

            //nombre_capitulo
            foreach ($request->all() as $key => $value) {

                $filtroCapituloNombre= substr($key, 0,15);

                if ($filtroCapituloNombre === 'nombre_capitulo'){

                    $params = explode('|', $key);               
                    $numeroBloque = $params[1];
                    $numero = $params[2];

                    $nombre = $value;

                    $iCapitulo = new Capitulo;                  

                    $iBloque = Bloque::where('numero',$numeroBloque)->where('curso_id',$curso_id)->first();

                    $iCapitulo->bloque_id = $iBloque->id;
                    $iCapitulo->nombre = $nombre;
                    $iCapitulo->curso_id = $curso_id;
                    $iCapitulo->numeroBloque = $numeroBloque;
                    $iCapitulo->numero = $numero;

                    $iCapitulo->save();
                }
            }

            //descripcion_capitulo
            foreach ($request->all() as $key => $value) {

                $filtroCapituloDescripcion = substr($key, 0,20);
                
                if ($filtroCapituloDescripcion === 'descripcion_capitulo'){

                    $params = explode('|', $key);               
                    $numeroBloque = $params[1];
                    $numero = $params[2];

                    $descripcion = $value;

                    $iCapitulo = Capitulo::where('curso_id',$curso_id)->where('numeroBloque',$numeroBloque)->where('numero',$numero)->first();
                    
                    $iCapitulo->descripcion = $descripcion;
                    $iCapitulo->save();         
                }
            }

            //video_capitulo
            foreach ($request->all() as $key => $value) {

                $filtroCapituloVideo = substr($key, 0,14);
                
                if ($filtroCapituloVideo === 'video_capitulo'){

                    $params = explode('|', $key);               
                    $numeroBloque = $params[1];
                    $numero = $params[2];

                    $video = Input::file($key);

                    $filename = $curso_id.$numeroBloque.$numero.$video->getClientOriginalName();
                    $path = public_path().'/cursovideos/';
                    $video->move($path, $filename);
                   
                    $iCapitulo = Capitulo::where('curso_id',$curso_id)->where('numeroBloque',$numeroBloque)->where('numero',$numero)->first();
                    
                    $iCapitulo->video = $filename;
                    $iCapitulo->save();         
                }
            }

            $curso->save();

            return redirect('/home')->with('finSubida', '1');

            \Session::flash('flash_message', 'Tu curso ha sido creado');

            //return redirect()->action('HomeController@show');

        
        }else{
           
            $msg = cursosController::devolverErrorValidacion($error['v']);          
            return redirect('/');
            
            //return view('crear-curso', compact('msg')); 
            //si falla la validacion
            //cursosController::devolverErrorValidacion($error['campo'], $error['v'], $error['numero'],$error['numeroBloque']);
        }
    }

    public function subirVideo(Request $request){

        $curso_id = $request->curso_id;

        foreach ($request->all() as $key => $value) {

            $filtroSubirVideo = substr($key, 0,14);
                
            if ($filtroSubirVideo === 'video_capitulo'){
               
                $params = explode('|', $key);               
                $numeroBloque = $params[1];


                $numero = $params[2];

                $video = Input::file($key);

                $filename = $curso_id.$numeroBloque.$numero.$video->getClientOriginalName();

                $path = public_path().'/cursovideos/';
                $video->move($path, $filename);
            }

        }

        

    }

    public function ValidacionF($request)
    {
        
        //validacion parte 1 & 2 & 4
        $v = \Validator::make($request->all(), [
       
            'resumen'       => 'required',
            'descripcion'   => 'required',
            'video_promo'   => 'required',        

        ]);        

        // Devolvemos el error
        if ($v->fails()){           
            $rerror = ['error'=>1, 'v'=> $v];
            return $rerror;                    
        }

        //validamos los campos de pago
        if($request->pago == 1){

             $v = \Validator::make($request->all(), [
                 
                'precio'        => 'required',
                'exclusividad'  => 'required'

            ]);        
            
        }

        // Devolvemos el error
        if ($v->fails()){           
            $rerror = ['error'=>1, 'v'=> $v];
            return $rerror;                    
        }

        foreach ($request->all() as $key => $value) {

            $filtroBloque= substr($key, 0,6);
            $filtroCapituloNombre= substr($key, 0,15);
            $filtroCapituloDescripcion = substr($key, 0,20);
            $filtroCapituloVideo = substr($key, 0,14);

            if ($filtroBloque === 'bloque'){

                $params = explode('|', $key);               
                $numero = $params[1];

                // Validacion
                $v = \Validator::make($request->all(), [
                    $key       => 'required'        
                    ]);

                // Devolvemos el error
                if ($v->fails()){
                   
                    $rerror = ['error'=>1, 'v'=> $v];
                    return $rerror;                    
                }              

            }

            if ($filtroCapituloNombre === 'nombre_capitulo'){

                $params = explode('|', $key);               
                $numeroBloque = $params[1];
                $numero = $params[2];

                // Validacion
                $v = \Validator::make($request->all(), [
                    $key       => 'required'        
                    ]);

                // Devolvemos el error
                if ($v->fails()){
                    
                    $rerror = ['error'=>1, 'v'=> $v];
                    return $rerror;                    
                }          

            }

            if ($filtroCapituloDescripcion === 'descripcion_capitulo'){

                $params = explode('|', $key);               
                $numeroBloque = $params[1];
                $numero = $params[2];

                // Validacion
                $v = \Validator::make($request->all(), [
                    $key       => 'required'        
                    ]);

                // Devolvemos el error
                if ($v->fails()){
                    
                    $rerror = ['error'=>1, 'v'=> $v];
                    return $rerror;                    
                }      

            }

            if ($filtroCapituloVideo === 'video_capitulo'){

                $params = explode('|', $key);               
                $numeroBloque = $params[1];
                $numero = $params[2];

                // Validacion
                $v = \Validator::make($request->all(), [
                    $key       => 'required'        
                    ]);

                // Devolvemos el error
                if ($v->fails()){
                    
                    $rerror = ['error'=>1, 'v'=> $v];
                    return $rerror;                    
                }else{
                    $rerror = ['error'=>0];
                    return $rerror;
                }                 

            }

        }
    }

    public function devolverErrorValidacion($v)
    {
       
        foreach ($v->messages()->getMessages() as $field_name => $messages) {
           
            $mensaje = ($messages[0]);
           
            $msg =  $mensaje;     
        }   
       
        return $msg;  
         
    }   


    public function editar($id)
    {
        $categorias = Categoria::lists('name', 'id');

        $curso = Curso::findOrFail($id);

        $bloques = Bloque::where('curso_id',$id)->get();

        return view('editar-curso', compact('curso', 'bloques', 'categorias'));
    }


    public function actualizar($id, Request $request)
    {
        $curso = Curso::findOrFail($id);


        $curso->update($request->all());

        $curso->categorias()->attach($request->input('categorias'));


        return redirect()->action('HomeController@show');
    }

   

}
