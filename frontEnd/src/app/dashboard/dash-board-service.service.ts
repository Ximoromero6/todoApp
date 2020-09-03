import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { HttpClient, HttpHeaders } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class DashBoardServiceService {

  url: string = 'http://localhost:8080/';
  constructor(private http: HttpClient) { }

  //Método para obtener todas las tareas
  obtenerTareas(userToken): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );

    let body = 'userToken=' + userToken;
    return this.http.post(this.url + 'obtenerTareas', body, {
      headers: headers
    });
  }

  //Método para eliminar una tarea
  eliminarTarea(id): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );

    let body = 'id=' + id;
    return this.http.post(this.url + 'eliminarTarea', body, {
      headers: headers
    });
  }

  //Método para añadir un comentario
  addComentario(token, idTarea, comentario): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );

    let body = 'token=' + token + '&idTarea=' + idTarea + '&comentario=' + comentario;
    return this.http.post(this.url + 'addComentario', body, {
      headers: headers
    });
  }

  //Método para eliminar un comentario
  eliminarComentario(idTarea, idComentario): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );

    let body = 'idTarea=' + idTarea + '&idComentario=' + idComentario;
    return this.http.post(this.url + 'eliminarComentario', body, {
      headers: headers
    });
  }

  //Método para completar una tarea con el botón de la izquierda
  completarTarea(idTarea): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );

    let body = 'idTarea=' + idTarea;
    return this.http.post(this.url + 'completarTarea', body, {
      headers: headers
    });
  }

  //Función para añadir un participante extra a una tarea
  agregarParticipante(tokenSesion, idTarea, emailUsuario): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );

    let body = 'tokenSesion=' + tokenSesion + '&idTarea=' + idTarea + '&emailUsuario=' + emailUsuario;
    return this.http.post(this.url + 'agregarParticipante', body, {
      headers: headers
    });
  }

  //Función para eliminar un participante de una tarea
  eliminarParticipanteExtra(idTarea, idUsuario): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );

    let body = 'idTarea=' + idTarea + '&idUsuario=' + idUsuario;
    return this.http.post(this.url + 'eliminarParticipanteExtra', body, {
      headers: headers
    });
  }

  //Función para editar el titulo de una tarea
  editarTitulo(idTarea, texto): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );

    let body = 'idTarea=' + idTarea + '&texto=' + texto;
    return this.http.post(this.url + 'editarTitulo', body, {
      headers: headers
    });
  }

  //Función para editar la descripción de una tarea
  editarDescripcion(idTarea, texto): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );

    let body = 'idTarea=' + idTarea + '&texto=' + texto;
    return this.http.post(this.url + 'editarDescripcion', body, {
      headers: headers
    });
  }
}
