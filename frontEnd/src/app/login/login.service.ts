import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root',
})
export class LoginService {
  url: string = 'http://localhost:8080/';

  constructor(private http: HttpClient) {}

  validarLogin(email, clave): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );
    let body = 'email=' + email + '&clave=' + clave;
    return this.http.post(this.url + 'validarLogin', body, {
      headers: headers,
    });
  }
}
