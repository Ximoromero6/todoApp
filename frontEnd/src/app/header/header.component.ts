import { Component, OnInit, Output, EventEmitter, Renderer2, Inject, ViewChild } from '@angular/core';
import { DomSanitizer } from '@angular/platform-browser';
import { ServicioService } from './servicio.service';
import { FormGroup, FormBuilder, Validators } from '@angular/forms';
import { DOCUMENT, JsonPipe } from '@angular/common';
import { Router } from '@angular/router';
import { NotaComponent } from '../nota/nota.component';
import { SharedDashboardHeaderServiceService } from '../home/shared-dashboard-header-service.service';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-header',
  templateUrl: './header.component.html',
  styleUrls: ['./header.component.scss'],
})
export class HeaderComponent implements OnInit {
  @Output("cerrarSesion") cerrarSesion = new EventEmitter<any>();

  subscription: Subscription;
  tasks;
  sessionTasks;

  constructor(
    private sanitizer: DomSanitizer,
    private servicio: ServicioService,
    private formBuilder: FormBuilder,
    public router: Router,
    private SharedDashboardHeaderServiceService: SharedDashboardHeaderServiceService,
    //Esto es para el tema oscuro, REVISAR!!
    /* @Inject(DOCUMENT) private document: Document,
    private renderer: Renderer2 */
  ) {
    this.data = localStorage.getItem('userData') != null ? JSON.parse(atob(localStorage.getItem('userData'))) : JSON.parse(atob(sessionStorage.getItem('userData')));

    this.subscription = SharedDashboardHeaderServiceService.task$.subscribe(
      (response) => {
        this.tasks = response;
      },
      (error) => {
        console.error(error);
      }
    );
  }

  //Formulario datos extra
  formularioDatosExtra: FormGroup;

  //Formulario para cambiar la contraseña
  formularioCambiarClave: FormGroup;

  //Formulario para crear un equipo
  formularioCrearEquipo: FormGroup;

  //Formulario para unirse a un equipo
  formularioUnirseEquipo: FormGroup;

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

  flag: boolean = true;

  //Datos del equipo
  datosEquipo;
  imagenEquipo: string;
  nombreEquipo: string;
  creacionEquipo: string;
  descripcionEquipo: string;
  usersCountEquipo;
  adminEquipo;
  usersDataEquipo;

  //Para unirse a equipo
  idEquipoHidden;

  @ViewChild(NotaComponent) child;

  addTarea() {
    document.getElementById('addTareaButtonHeader').addEventListener('click', () => {
      this.child.showTask();
      this.child.hideTask();
    });
  }

  public formGroup = this.formBuilder.group({
    file: [null, Validators.required]
  });

  ngOnInit(): void {
    this.addTarea();
    if (this.router.url === '/home/completadas') {
      (document.querySelector('.menuItem').lastChild as HTMLElement).classList.add('active');
    }

    //this.renderer.setAttribute(document.documentElement, 'data-theme', 'dark');
    this.obtenerDatosEquipo(this.data.token);

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

    /* Funcionalidad de recordar el estado del menú (open / close) con LocalStorage */

    let sideBarMenu = document.getElementById('sideBarMenu');
    let openMenuIcon = document.getElementById('openMenuIcon');
    let mainContainerHome = document.getElementById('mainContainerHome');
    let mainContainerTareasCompletadas = document.getElementById('mainContainerTareasCompletadas');

    if (localStorage.getItem('menu') === 'close') {
      sideBarMenu.classList.add('active');
      openMenuIcon.style.marginLeft = '30px';
      mainContainerHome != undefined ? mainContainerHome.style.paddingLeft = '0' : '';
      mainContainerTareasCompletadas != undefined ? mainContainerTareasCompletadas.style.paddingLeft = '0' : '';
    } else {
      sideBarMenu.classList.remove('active');
    }

    //Menú cerrar
    document.getElementById('closeMenuIcon').addEventListener('click', () => {
      localStorage.setItem('menu', 'close');
      sideBarMenu.classList.add('active');
      openMenuIcon.style.marginLeft = '30px';
      mainContainerHome != undefined ? mainContainerHome.style.paddingLeft = '0' : '';
      mainContainerTareasCompletadas != undefined ? mainContainerTareasCompletadas.style.paddingLeft = '0' : '';
    });

    //Menú abrir
    openMenuIcon.addEventListener('click', () => {
      localStorage.setItem('menu', 'open');
      sideBarMenu.classList.remove('active');
      mainContainerHome != undefined ? mainContainerHome.style.paddingLeft = '250px' : '';
      mainContainerTareasCompletadas != undefined ? mainContainerTareasCompletadas.style.paddingLeft = '250px' : '';
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
      if (e.srcElement.className !== "noCloseMenu") {
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
              item.style.padding = '10px 4px';
              item.style.listStyle = 'none';
              item.style.cursor = 'pointer';
              item.style.borderBottom = '1px solid #ccc';
              item.style.fontSize = '14px';

              item.addEventListener('click', (e) => {
                this.formularioDatosExtra.get('ubicacion').setValue(e.target.textContent);
                document.getElementById("showData").style.display = 'none';
              });

              item.addEventListener('mouseover', () => {
                item.style.background = "#f6f8f9";
              });

              item.addEventListener('mouseout', () => {
                item.style.background = "#ffffff";
              });
            }

          } else {
            document.getElementById('showData').textContent = '';
          }

        }, (error) => {
          console.log(error);
        });
    });

    //Comprobar que las dos claves coinciden
    this.formularioCambiarClave = this.formBuilder.group({
      claveActual: [
        '',
        Validators.required,
      ],
      claveNueva: [
        '',
        Validators.compose([Validators.minLength(6), Validators.required]),
      ],
      claveNuevaDos: [
        '',
        Validators.compose([Validators.minLength(6), Validators.required]),
      ]
    }, { validator: this.checkPasswords });

    //Formulario Crear equipo
    this.formularioCrearEquipo = this.formBuilder.group({
      crearEquipoField: ['', Validators.required]
    });

    //Formulario unirse a un equipo
    this.formularioUnirseEquipo = this.formBuilder.group({
      unirseEquipoField: ['', Validators.required]
    });

  } /* Fin ngOnInit */

  //Función para añadir o quitar la clase active al menú leteral izquierdo
  toggleClass(e) {
    let tg = (<HTMLElement>(<HTMLElement>e.target));
    let list = document.querySelectorAll('.menuItem');

    list.forEach(element => {
      element.classList.remove('active');
    });
    if (tg.className == 'child') {
      (tg.parentElement as HTMLElement).classList.add('active');
    } else {
      (tg as HTMLElement).classList.add('active');
    }

  }

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
          document.getElementById('preloader').style.display = "none";
          if (response.status) {
            if (localStorage.getItem('userData') != null) {
              localStorage.setItem("userData", btoa(JSON.stringify(response.data)));
            } else {
              sessionStorage.setItem("userData", btoa(JSON.stringify(response.data)));
            }
            this.data.imagen = response.data.imagen;
            this.imagen = this.sanitizer.bypassSecurityTrustUrl(`${window.location.origin}/assets/uploads/${this.data.usuario}/${response.data.imagen}`);
            this.updateLocal(this.data);


          } else {
            this.mensajeNotificacion = response.response;
            this.statusNotificacion = 'error';
            this.iconoNotificacion = 'fas fa-exclamation-circle';
            this.ocultarNotificacion(false);
          }

          setTimeout(() => {
            this.ocultarNotificacion(true);
          }, 5000);
        },
        (error) => {
          console.log(error);
        }
      );
    }
  }

  //Función que comprueba si dos claves coinciden
  checkPasswords(group: FormGroup) {
    let pass = group.get('claveNueva').value;
    let confirmPass = group.get('claveNuevaDos').value;

    return pass === confirmPass ? null : { notSame: true }
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

  //Función validar contraseña internamente para poder cambiarla
  validarDatosCambiarClave() {
    if (this.flag) {
      if (this.formularioCambiarClave.invalid) {
        this.formularioCambiarClave.get('claveActual').markAsDirty();
        this.formularioCambiarClave.get('claveNueva').markAsDirty();
        this.formularioCambiarClave.get('claveNuevaDos').markAsDirty();
        return;
      }

      this.servicio.cambiarClave(
        this.data.token, this.formularioCambiarClave.get('claveActual').value, this.formularioCambiarClave.get('claveNuevaDos').value
      ).subscribe(
        (response) => {
          if (response.status) {
            this.statusNotificacion = 'success';
            this.iconoNotificacion = 'fas fa-check-circle';
            this.mensajeNotificacion = response.response;
            this.formularioCambiarClave.reset();
          } else {
            this.statusNotificacion = 'error';
            this.iconoNotificacion = 'fas fa-exclamation-circle';
            this.mensajeNotificacion = response.response;
          }
          this.ocultarNotificacion(false);
          setTimeout(() => {
            this.ocultarNotificacion(true);
          }, 5000);
        },
        (error) => {
          console.log(error);
        }
      );
    }
  }

  openTabTeam(tab) {
    document.querySelector('#tab3 .container .bottom').classList.add('visible');
    let i: any;
    let x: any = document.getElementsByClassName("tab");
    for (i = 0; i < x.length; i++) {
      x[i].style.display = "none";
    }
    document.getElementById(tab).style.display = "flex";
  }

  validarDatosCrearEquipo() {
    if (this.formularioCrearEquipo.get('crearEquipoField').value) {
      this.servicio.crearEquipo(this.data.token, this.formularioCrearEquipo.get('crearEquipoField').value).subscribe(
        (response) => {
          console.log(response);
          if (response.status) {

            let equipo = this.formularioCrearEquipo.get('crearEquipoField').value;

            this.statusNotificacion = 'success';
            this.iconoNotificacion = 'fas fa-check-circle';
            this.mensajeNotificacion = response.response;
            this.data.equipo = equipo;
            this.equipo = equipo;
            this.formularioDatosExtra.get('equipo').setValue(equipo);
            this.updateLocal(this.data);
            this.formularioCrearEquipo.reset();
            this.obtenerDatosEquipo(this.data.token);
          } else {
            this.statusNotificacion = 'error';
            this.iconoNotificacion = 'fas fa-exclamation-circle';
            this.mensajeNotificacion = response.response;
          }
          this.ocultarNotificacion(false);
          setTimeout(() => {
            this.ocultarNotificacion(true);
          }, 5000);
        },
        (error) => {
          console.log(error);
        })
    }
  }

  //Get team data
  obtenerDatosEquipo(token) {
    //Función para obtener los datos del equipo de la BD
    this.servicio.obtenerDatosEquipo(token).subscribe(
      (response) => {
        console.log(response);
        if (response.status) {
          this.datosEquipo = response.response;
          this.imagenEquipo = this.datosEquipo.imagen !== '' ? this.datosEquipo.imagen : '';
          this.nombreEquipo = this.datosEquipo.nombre !== '' ? this.datosEquipo.nombre : '';
          this.descripcionEquipo = this.datosEquipo.descripcion !== '' ? this.datosEquipo.descripcion : '';
          this.creacionEquipo = this.datosEquipo.creacion !== '' ? this.datosEquipo.creacion : '';
          this.adminEquipo = this.datosEquipo.admin !== '' ? this.datosEquipo.admin : '';
          this.usersCountEquipo = this.datosEquipo.usersCount !== '' ? this.datosEquipo.usersCount : '';
          this.usersDataEquipo = this.datosEquipo.usersData !== '' ? this.datosEquipo.usersData : '';
        }
      },
      (error) => {
        console.log(error);
      });
  }

  //Método para actualizar el localStorage
  updateLocal(value) {
    localStorage.setItem("userData", btoa(JSON.stringify(value)));
  }

  //Función para abandonar un grupo
  abandonarEquipo(token) {
    this.servicio.abandonarEquipo(token).subscribe(
      (response) => {
        if (response.status) {
          this.statusNotificacion = 'success';
          this.iconoNotificacion = 'fas fa-check-circle';
          this.mensajeNotificacion = response.response;
          this.data.equipo = "";
          this.equipo = "";
          this.formularioDatosExtra.get('equipo').setValue("");
          this.updateLocal(this.data);
        }
        this.ocultarNotificacion(false);
        setTimeout(() => {
          this.ocultarNotificacion(true);
        }, 5000);
      },
      (error) => {
        console.log(error);
      }
    );
  }

  //Función para buscar un equipo
  buscarEquipo() {
    this.servicio.buscarEquipo(this.formularioUnirseEquipo.get('unirseEquipoField').value).subscribe(
      (response) => {
        console.log(response);
        document.getElementById("dropDownSearchTeam").style.display = 'block';
        if (response.status) {
          document.getElementById('dropDownSearchTeam').textContent = "";
          for (let r of response.response) {
            let element = document.createElement('li');
            element.setAttribute('data-id', r.id);
            element.classList.add('listItemTeam');

            let stylesLi = {
              background: '#e8ecee',
              display: 'flex',
              alignItems: 'center',
              padding: '6px',
              listStyle: 'none',
              borderTop: '1px solid #ccc',
              borderBottom: '1px solid #ccc',
              cursor: 'pointer'
            };
            Object.assign(element.style, stylesLi);

            let img = document.createElement('img');
            img.src = `../../assets/teams/${r.imagen}`;

            let stylesImg = {
              borderRadius: '50%',
              width: '30px',
              height: '30px',
              objectFit: 'cover',
              marginRight: '10px'
            };

            Object.assign(img.style, stylesImg);
            element.insertBefore(img, element.childNodes[0]);

            let text = document.createTextNode(r.nombre);
            element.appendChild(text);
            document.getElementById('dropDownSearchTeam').appendChild(element);
          }

          let liItems: any = document.getElementsByClassName('listItemTeam');
          let buttonJoin: any = document.querySelector('#joinTeam .inline button');
          for (let item of liItems) {
            item.addEventListener('mouseover', () => {
              item.style.background = "#dee1e3";
            });
            item.addEventListener('mouseout', () => {
              item.style.background = "#e8ecee";
            });
            item.addEventListener('click', (e) => {
              this.formularioUnirseEquipo.get('unirseEquipoField').setValue(e.target.textContent);
              this.idEquipoHidden = e.target.getAttribute('data-id');
              document.getElementById("dropDownSearchTeam").style.display = 'none';
              buttonJoin.classList.remove('disabled');
              buttonJoin.disabled = false;
            });
          }
          if (this.formularioUnirseEquipo.get('unirseEquipoField').value == '') {
            buttonJoin.classList.add('disabled');
            buttonJoin.disabled = true;
            document.getElementById("dropDownSearchTeam").style.display = 'none';
          }

        } else {
          document.getElementById("dropDownSearchTeam").style.display = 'none';
        }
      },
      (error) => {
        console.log(error);
      }
    );
  }

  //Función para unirse a un equipo
  unirseEquipo() {
    this.servicio.unirseEquipo(this.data.token, this.idEquipoHidden, this.formularioUnirseEquipo.get('unirseEquipoField').value).subscribe(
      (response) => {
        this.mensajeNotificacion = response.response;
        if (response.status) {
          this.statusNotificacion = 'success';
          this.iconoNotificacion = 'fas fa-check-circle';
          let updated = this.formularioUnirseEquipo.get('unirseEquipoField').value;
          this.data.equipo = updated;
          this.equipo = updated;
          this.formularioDatosExtra.get('equipo').setValue(updated);
          this.updateLocal(this.data);
          this.obtenerDatosEquipo(this.data.token);
          this.formularioUnirseEquipo.reset();
        } else {
          this.statusNotificacion = 'error';
          this.iconoNotificacion = 'fas fa-exclamation-circle';
        }
        this.ocultarNotificacion(false);
        setTimeout(() => {
          this.ocultarNotificacion(true);
        }, 5000);
      },
      (error) => {
        console.error(error);
      }
    );
  }

  //Editar datos de equipo
  changeEditButton() {
    let editButton = document.getElementById('buttonEditDataTeam');
    editButton.style.display = 'none';

    let confirm = document.getElementById('confirmEditDataTeam');
    confirm.style.display = 'flex';

    let name: any = document.getElementById('teamName');
    name.contentEditable = true;
    name.classList.toggle('active');

    let description: any = document.getElementById('teamDescription');
    description.contentEditable = true;
    description.classList.toggle('active');
  }


  editarDatosEquipo() {
    //Cuando le demos al botón de guardar se envían los datos del formulario
    let confirm = document.getElementById('confirmEditDataTeam');
    let name: any = document.getElementById('teamName');
    let description: any = document.getElementById('teamDescription');
    let editButton = document.getElementById('buttonEditDataTeam');

    name.contentEditable = false;
    description.contentEditable = false;
    name.classList.remove('active');
    description.classList.remove('active');
    confirm.style.display = 'none';
    editButton.style.display = 'flex';

    let newTitle = name.textContent;
    let newDescription = description.textContent;

    //Llamamos al back
    this.servicio.actualizarDatosEquipo(this.data.token, this.datosEquipo.id, newTitle, newDescription).subscribe(
      (response) => {
        this.mensajeNotificacion = response.response;
        if (response.status) {
          this.statusNotificacion = 'success';
          this.iconoNotificacion = 'fas fa-check-circle';
        } else {
          this.statusNotificacion = 'error';
          this.iconoNotificacion = 'fas fa-exclamation-circle';
        }
        this.ocultarNotificacion(false);
        setTimeout(() => {
          this.ocultarNotificacion(true);
        }, 5000);
      },
      (error) => {
        console.log(error);
      }
    );

  }

  //Eliminar un usuario
  deleteUser(evt, id) {
    this.servicio.eliminarUsuarioEquipo(this.data.token, this.datosEquipo.id, id).subscribe(
      (response) => {
        this.mensajeNotificacion = response.response;
        if (response.status) {
          this.statusNotificacion = 'success';
          this.iconoNotificacion = 'fas fa-check-circle';
          let parent = (<HTMLElement>(<HTMLElement>evt.target).parentNode.parentNode);
          parent.remove();
        } else {
          this.statusNotificacion = 'error';
          this.iconoNotificacion = 'fas fa-exclamation-circle';
        }
        this.ocultarNotificacion(false);
        setTimeout(() => {
          this.ocultarNotificacion(true);
        }, 5000);
      },
      (error) => {
        console.log(error);
      }
    );
  }

  //Función para buscar tareas con el buscador
  searchTask() {
    let text = (event.target as HTMLInputElement).value;
    this.servicio.searchTask(text).subscribe(
      (response) => {
        //Si encuentra alguna tarea mostrarla
        if (response.status) {
          let item: any = document.createElement('li');
          item.appendChild(document.createTextNode('TEST'));
          document.getElementById('searchBoxResult').appendChild(item);
        } else {

        }
      },
      (error) => {
        console.error(error);
      }
    );
  }

}