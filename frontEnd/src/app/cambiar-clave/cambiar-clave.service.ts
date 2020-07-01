import { Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { HttpHeaders, HttpClient } from '@angular/common/http';

@Injectable({
  providedIn: 'root'
})
export class CambiarClaveService {

  url: string = 'http://localhost:8080/';

  constructor(
    private http: HttpClient
  ) { }

  resetClave(email): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );
    let body = 'email=' + email;
    return this.http.post(this.url + 'resetClave', body, {
      headers: headers
    });
  }
}
