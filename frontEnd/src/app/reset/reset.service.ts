import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ResetService {

  url: string = 'http://localhost:8080/';

  constructor(
    private http: HttpClient
  ) { }

  verifyTokenReset(encodedToken): Observable<any> {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );
    let body = 'token=' + encodedToken;
    return this.http.post(this.url + 'verifyTokenReset', body, {
      headers: headers
    });
  }

  reset(clave, claveConfirm, token) {
    let headers = new HttpHeaders().set(
      'Content-Type',
      'application/x-www-form-urlencoded; charset=UTF-8'
    );
    /* let body = `clave=${clave}&claveConfirm=${claveConfirm}&token=${token}`; */
    let body = 'clave=' + clave + '&claveConfirm=' + claveConfirm + '&token=' + token;
    return this.http.post(this.url + 'reset', body, {
      headers: headers
    });
  }
}
