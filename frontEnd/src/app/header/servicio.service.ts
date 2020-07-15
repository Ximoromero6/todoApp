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
}
