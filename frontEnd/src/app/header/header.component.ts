import { Component, OnInit, Output, EventEmitter, Renderer2, Inject } from '@angular/core';
import { DomSanitizer } from '@angular/platform-browser';
import { ServicioService } from './servicio.service';
import { FormGroup, FormBuilder, Validators } from '@angular/forms';
import { DOCUMENT } from '@angular/common';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.scss']
})
export class HeaderComponent implements OnInit {
  @Output("cerrarSesion") cerrarSesion = new EventEmitter<any>();

  constructor(
    private sanitizer: DomSanitizer,
    private servicio: ServicioService,
    private formBuilder: FormBuilder,

    //Esto es para el tema oscuro, REVISAR!!
    @Inject(DOCUMENT) private document: Document,
    private renderer: Renderer2,
  ) {
    this.data = localStorage.getItem('userData') != null ? JSON.parse(atob(localStorage.getItem('userData'))) : JSON.parse(atob(sessionStorage.getItem('userData')));
  }

  //Formulario datos extra
  formularioDatosExtra: FormGroup;

  //Datos del usuario
  data: any
  nombre: string
  usuario: string
  imagen: any
  ubicacion: string
  rol: string
  equipo: string
  descripcion: string

  //Notificaciones variables
  statusNotificacion: string;
  iconoNotificacion: string;
  mensajeNotificacion: string;
  mostrarNotificacion: boolean = true;

  public formGroup = this.formBuilder.group({
    file: [null, Validators.required]
  });

  ngOnInit(): void {
    //this.renderer.setAttribute(document.documentElement, 'data-theme', 'dark');
    console.log(this.data);

    //Añadimos las variables
    this.nombre = this.data.nombre != '' ? this.data.nombre : this.data.usuario;
    this.ubicacion = this.data.ubicacion;
    this.rol = this.data.rol;
    this.equipo = this.data.equipo;
    this.descripcion = this.data.descripcion;

    if (this.data.imagen) {
      console.log(this.data.imagen);
      this.imagen = this.sanitizer.bypassSecurityTrustUrl(`${window.location.origin}/assets/uploads/${this.data.usuario}/${this.data.imagen}`);
    } else {
      this.imagen = this.sanitizer.bypassSecurityTrustUrl(`${window.location.origin}/assets/images/user.svg`);
    }

    //Creamos el formGroup
    this.formularioDatosExtra = this.formBuilder.group({
      nombre: [this.nombre],
      ubicacion: [this.ubicacion],
      rol: [this.rol],
      equipo: [this.equipo],
      descripcion: [this.descripcion],
    });

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

    //Esconder dropdown menu y overlay
    let hideDrop = document.getElementById('dropDownMenu');
    let hideOverlay = document.querySelector('.overlayPopupProfileSettings');

    window.onclick = function (e) {
      if (e.srcElement.className !== "noClose") {
        hideDrop.classList.remove('open');
      } else {
        hideDrop.classList.add('open');
      }

      if (e.srcElement.className.includes("overlayPopupProfileSettings")) {
        hideOverlay.classList.remove('open');
      }
    }

    document.getElementById('profileSettingsButton').addEventListener('click', () => {
      hideOverlay.classList.add('open');
    });

    document.getElementById('closeButtonOverlay').addEventListener('click', () => {
      hideOverlay.classList.remove('open');
    });

    let el = document.querySelectorAll('.tablink');

    el.forEach(e => {
      e.addEventListener('click', () => {
        el.forEach(element => {
          element.classList.remove('active');
        });
        e.classList.add('active');
      });
    });

    //Agregar funcionalidad mostrar ubicación al escribir
    let ubicacionField = (<HTMLInputElement>document.getElementById('ubicacionField'));
    ubicacionField.addEventListener('keyup', () => {
      this.servicio.getUbicacion(ubicacionField.value).subscribe(
        (response) => {
          document.getElementById("showData").style.display = 'block';
          if (ubicacionField.value != '') {
            let lista = response._embedded["city:search-results"];
            document.getElementById('showData').textContent = '';
            Object.keys(lista).map(function (key, index) {
              let finalCity = lista[key];
              let node = document.createElement("li");
              node.classList.add('listItem');
              let textnode = document.createTextNode(finalCity.matching_full_name);
              node.appendChild(textnode);
              document.getElementById("showData").appendChild(node);
            });
            let liItems: any = document.getElementsByClassName('listItem');

            for (let item of liItems) {
              item.style.padding = '8px 4px';
              item.style.listStyle = 'none';
              item.style.cursor = 'pointer';
              item.style.borderBottom = '1px solid #ccc';
              item.style.fontSize = '14px';

              item.addEventListener('click', (e) => {
                this.formularioDatosExtra.get('ubicacion').setValue(e.target.textContent);
                document.getElementById("showData").style.display = 'none';
              });
            }

          } else {
            document.getElementById('showData').textContent = '';
          }

        }, (error) => {
          console.log(error);
        });
    });

  } /* Fin ngOnInit */

  //Función get file
  public onFileChange(event) {
    if (event.target.files.length > 0) {
      const file = event.target.files[0];
      this.formGroup.get('file').setValue(file);
      document.getElementById('preloader').style.display = "flex";
      this.servicio.cambiarFoto(
        file,
        this.data
      ).subscribe(
        (response) => {
          console.log(response);
          document.getElementById('preloader').style.display = "none";
          if (localStorage.getItem('userData') != null) {
            localStorage.setItem("userData", btoa(JSON.stringify(response.data)));
          } else {
            sessionStorage.setItem("userData", btoa(JSON.stringify(response.data)));
          }
          this.data.imagen = response.data.imagen;
          this.imagen = this.sanitizer.bypassSecurityTrustUrl(`${window.location.origin}/assets/uploads/${this.data.usuario}/${response.data.imagen}`);
        },
        (error) => {
          console.log(error);
        }
      );
    }
  }

  //Función para mostrar/ocultar las tabs
  openTab(element) {
    let i, tabContent, tablinks;
    tabContent = document.getElementsByClassName("tabContent");
    for (i = 0; i < tabContent.length; i++) {
      tabContent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tablink");
    for (i = 0; i < tablinks.length; i++) {
      tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(element).style.display = "block";
  }

  cerrarSesionFunction() {
    if (confirm('¿Seguro que quieres cerrar sesión?')) {
      this.cerrarSesion.emit();
    }
  }

  //Función para actualizar los datos del usuario
  validarDatosDatosExtra() {
    if (this.data.nombre != this.formularioDatosExtra.value.nombre || this.data.ubicacion != this.formularioDatosExtra.value.ubicacion || this.data.rol != this.formularioDatosExtra.value.rol || this.data.equipo != this.formularioDatosExtra.value.equipo || this.data.descripcion != this.formularioDatosExtra.value.descripcion) {
      this.data.nombre = this.formularioDatosExtra.value.nombre;
      this.data.ubicacion = this.formularioDatosExtra.value.ubicacion;
      this.data.rol = this.formularioDatosExtra.value.rol;
      this.data.equipo = this.formularioDatosExtra.value.equipo;
      this.data.descripcion = this.formularioDatosExtra.value.descripcion;

      this.servicio.updateDatosExtra(this.data).subscribe(
        (response) => {
          if (response.status) {
            if (localStorage.getItem('userData') != null) {
              localStorage.setItem("userData", btoa(JSON.stringify(response.data)));
            } else {
              sessionStorage.setItem("userData", btoa(JSON.stringify(response.data)));
            }
            this.mensajeNotificacion = response.response;
            this.statusNotificacion = 'success';
            this.iconoNotificacion = 'fas fa-check-circle';
          }
          this.ocultarNotificacion(false);
          setTimeout(() => {
            this.ocultarNotificacion(true);
          }, 5000);
        },
        (error) => {
          console.log(error);
        });
    } else {
      this.mensajeNotificacion = 'No hay cambios para guardar...';
      this.statusNotificacion = 'error';
      this.iconoNotificacion = 'fas fa-exclamation-circle';
      this.ocultarNotificacion(false);
      setTimeout(() => {
        this.ocultarNotificacion(true);
      }, 5000);
    }
  }

  ocultarNotificacion(value: boolean) {
    this.mostrarNotificacion = value;
  }

  eliminarImagen() {
    if (this.data.imagen !== '') {
      document.getElementById('preloader').style.display = "flex";
      this.servicio.eliminarFoto(this.data).subscribe((response) => {
        document.getElementById('preloader').style.display = "none";

        this.mensajeNotificacion = response.response;
        if (response.status) {

          //Mejor hacer función
          if (localStorage.getItem('userData') != null) {
            localStorage.setItem("userData", btoa(JSON.stringify(response.data)));
          } else {
            sessionStorage.setItem("userData", btoa(JSON.stringify(response.data)));
          }
          this.data.imagen = response.data.imagen;
          this.data.imagen = this.sanitizer.bypassSecurityTrustUrl(`${window.location.origin}/assets/images/user.svg`);
        } else {
          this.statusNotificacion = 'error';
          this.iconoNotificacion = 'fas fa-exclamation-circle';
          this.ocultarNotificacion(false);
          setTimeout(() => {
            this.ocultarNotificacion(true);
          }, 5000);
        }
      }, (error) => {
        document.getElementById('preloader').style.display = "none";
        console.log(error);
      });
    } else {
      this.mensajeNotificacion = 'No hay ninguna foto para eliminar...';
      this.statusNotificacion = 'error';
      this.iconoNotificacion = 'fas fa-exclamation-circle';
    }
  }

}
