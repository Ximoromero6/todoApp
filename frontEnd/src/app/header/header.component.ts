import { Component, OnInit, Output, EventEmitter } from '@angular/core';
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
  //Datos del usuario
  data: any
  nombre: string
  usuario: string
  imagen: any

  //Dropdown menu
  toggleDropDown: boolean = true;

  ngOnInit(): void {
    console.log(window.location.origin);
    //Añadimos las variables
    this.nombre = this.data.nombre != '' ? this.data.nombre : this.data.usuario;
    this.imagen = this.sanitizer.bypassSecurityTrustUrl(`${window.location.origin}/assets/images/${this.data.imagen}`);

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

    //Esconder dropdown menu
    let hideDrop = document.getElementById('dropDownMenu');

    window.onclick = function (e) {
      console.log(e.srcElement.className); // then e.srcElement.className has the class
      if (e.srcElement.className !== "testElement") {
        hideDrop.style.visibility = 'hidden';
      } else {
        hideDrop.style.visibility = 'visible';
      }


    }

    document.addEventListener('click', (e) => {
      //hideDrop.style.display = 'none';

      /* if (e.srcElement.classList != hideDrop) {
        console.log("a");
        //hideDrop.style.display = 'none';
      } else { console.log("b"); } */

    });
  }

  cerrarSesionFunction() {
    if (confirm('¿Seguro que quieres cerrar sesión?')) {
      this.cerrarSesion.emit();
    }
  }
}
