import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { HttpHeaders, HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class ServicioService {

  url: string = 'http://localhost:8080/';
  constructor(private http: HttpClient) { }

  //Método para cambiar la foto de perfil
  cambiarFoto(image, data): Observable<any> {
    const formData = new FormData();
    formData.append('file', image);
    formData.append('data', JSON.stringify(data));
    return this.http.post(this.url + 'cambiarFoto', formData);
  }

  //Método para cambiar la foto de perfil
  eliminarFoto(data): Observable<any> {
    const formData = new FormData();
    formData.append('data', JSON.stringify(data));
    return this.http.post(this.url + 'eliminarFoto', formData);
  }

  //Método para cambiar la foto de perfil
  getUbicacion(city): Observable<any> {
    const url = `https://api.teleport.org/api/cities/?search=${city}`;
    return this.http.get(url);
  }

  //Método para actualizar los datos del usuario
  updateDatosExtra(data): Observable<any> {
    const formData = new FormData();
    formData.append('data', JSON.stringify(data));
    return this.http.post(this.url + 'updateDatosExtra', formData);
  }

  //Método para cambiar la contraseña desde dentro
  cambiarClave(tokenUsuario, claveActual, claveNueva): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );
    let body = 'tokenUsuario=' + tokenUsuario + '&claveActual=' + claveActual + '&claveNueva=' + claveNueva;
    return this.http.post(this.url + 'cambiarClave', body, {
      headers: headers
    });
  }

  //Método para cambiar la contraseña desde dentro
  crearEquipo(tokenUsuario, nombre): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );
    let body = 'tokenUsuario=' + tokenUsuario + '&nombre=' + nombre;
    return this.http.post(this.url + 'crearEquipo', body, {
      headers: headers
    });
  }

  //Función para obtener los datos del equipo del usuario en sesión
  obtenerDatosEquipo(tokenUsuario): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );
    let body = 'tokenUsuario=' + tokenUsuario;
    return this.http.post(this.url + 'obtenerDatosEquipo', body, {
      headers: headers
    });
  }

  //Función para obtener los datos del equipo del usuario en sesión
  abandonarEquipo(tokenUsuario): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );
    let body = 'tokenUsuario=' + tokenUsuario;
    return this.http.post(this.url + 'abandonarEquipo', body, {
      headers: headers
    });
  }

  //Función para buscar equipos
  buscarEquipo(value): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );
    let body = 'value=' + value;
    return this.http.post(this.url + 'buscarEquipo', body, {
      headers: headers
    });
  }

  //Función para entrar en un equipo
  unirseEquipo(token, idGrupo, nombreGrupo): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );
    let body = 'token=' + token + '&idGrupo=' + idGrupo + '&nombreGrupo=' + nombreGrupo;
    return this.http.post(this.url + 'unirseEquipo', body, {
      headers: headers
    });
  }

}
