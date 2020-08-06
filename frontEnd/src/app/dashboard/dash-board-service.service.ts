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
  eliminarTarea(tokenUsuario, idTarea): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );

    let body = 'tokenUsuario=' + tokenUsuario + '&idTarea=' + idTarea;
    return this.http.post(this.url + 'eliminarTarea', body, {
      headers: headers
    });
  }
}
