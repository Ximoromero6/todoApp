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
}
