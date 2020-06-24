import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';

@Injectable({
  providedIn: 'root'
})
export class ActivateAcountService {

  url: string = "http://localhost:8080/";

  constructor(private http: HttpClient) { }

  activateAccount(token): Observable<any> {
    let headers = new HttpHeaders().set('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    let body = 'token=' + token;
    return this.http.post(this.url + "activateAccount", body, { headers: headers });
  }
}
