import { Component, OnInit, Output, EventEmitter, ViewChild, ElementRef } from '@angular/core';
import { DomSanitizer } from '@angular/platform-browser';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.scss']
})
export class HeaderComponent implements OnInit {
  @Output("cerrarSesion") cerrarSesion = new EventEmitter<any>();

  constructor(private sanitizer: DomSanitizer) {
    this.data = localStorage.getItem('userData') != null ? JSON.parse(atob(localStorage.getItem('userData'))) : JSON.parse(atob(sessionStorage.getItem('userData')));

  }
  data: any
  nombre: string
  usuario: string
  imagen: any


  ngOnInit(): void {

    //Añadimos las variables
    this.nombre = this.data.nombre != '' ? this.data.nombre : this.data.usuario;
    this.imagen = this.sanitizer.bypassSecurityTrustUrl(this.data.imagen);
    //Menú cerrar
    document.getElementById('closeMenuIcon').addEventListener('click', () => {
      document.getElementById('sideBarMenu').classList.add('active');
      document.getElementById('openMenuIcon').style.marginLeft = '20px';
    });

    //Menú abrir
    document.getElementById('openMenuIcon').addEventListener('click', () => {
      document.getElementById('sideBarMenu').classList.remove('active');
    });

    //Input search
    document.querySelector('#inputSearch input').addEventListener('focus', () => {
      document.getElementById('inputSearch').classList.add('active');
    });
    document.querySelector('#inputSearch input').addEventListener('focusout', () => {
      document.getElementById('inputSearch').classList.remove('active');
    });
  }

  cerrarSesionFunction() {
    this.cerrarSesion.emit();
  }
}
