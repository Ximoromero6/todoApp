import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { HttpHeaders, HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class ServicioService {
  url: string = 'http://localhost:8080/';
  constructor(
    private http: HttpClient
  ) { }

  getUserNote(email, sesionEmail): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );

    let body = 'email=' + email + '&sesionEmail=' + sesionEmail;
    return this.http.post(this.url + 'getUserNote', body, {
      headers: headers
    });
  }

  //Función para crear una tarea
  addTarea(titulo, fecha, descripcion, usuarios): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );

    let body = 'titulo=' + titulo + '&fecha=' + fecha + '&descripcion=' + descripcion + '&usuarios=' + usuarios;
    return this.http.post(this.url + 'addTarea', body, {
      headers: headers
    });
  }
}
