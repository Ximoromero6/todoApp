import { Component, OnInit, ViewChild, ElementRef, Output, EventEmitter } from '@angular/core';
import { FormGroup, FormBuilder, Validators } from '@angular/forms';
import { ServicioService } from './servicio.service';

@Component({
  selector: 'app-nota',
  templateUrl: './nota.component.html',
  styleUrls: ['./nota.component.scss']
})
export class NotaComponent implements OnInit {

  formularioAddPerson: FormGroup;

  //Notificaciones variables
  statusNotificacion: string;
  iconoNotificacion: string;
  mensajeNotificacion: string;
  mostrarNotificacion: boolean = true;

  formularioAddTarea: FormGroup;
  finalData;
  userdata;
  users = [];

  @ViewChild('inputAdd') inputAdd: ElementRef;
  @ViewChild('inputTitle') inputTitle: ElementRef;

  @Output() evento = new EventEmitter<string>();

  getDatos(token): void {
    this.evento.next(token);
  }

  constructor(
    private formBuilder: FormBuilder,
    private servicio: ServicioService
  ) { }

  ngOnInit(): void {
    this.userdata = localStorage.getItem('userData') != null ? JSON.parse(atob(localStorage.getItem('userData'))) : JSON.parse(atob(sessionStorage.getItem('userData')));
    this.users.push(this.userdata.token);
    this.formularioAddPerson = this.formBuilder.group({
      email: ['', Validators.required],
      clave: ['', Validators.required]
    });
    /* var monthpicker = new MaterialDatepicker('#customDate', {
      lang: 'es'
    }); */

    this.formularioAddTarea = this.formBuilder.group({
      titulo: ['', Validators.required],
      descripcion: ['', Validators.required]
    });
    this.notaFuncion();
    /*     this.inputTitle.nativeElement.focus(); */
    // this.removeItem();

    //Obtener fecha seleccionada
    let todayDate: any = document.getElementById('todayDate');
    let tomorrowDate: any = document.getElementById('tomorrowDate');
    let customDate: any = document.getElementById('customDate');
    let noneDate: any = document.getElementById('noneDate');
    let customDataField: any = document.getElementById('customDataField');

    let today: any = new Date();
    let dd: number = today.getDate();
    let mm: number = today.getMonth() + 1;
    const yyyy = today.getFullYear();

    this.finalData = `${yyyy}/${mm}/${dd}`;

    todayDate.addEventListener('click', () => {
      if (todayDate.checked && !tomorrowDate.checked && !customDate.checked && !noneDate.checked) {
        document.getElementById('labelTodayDate').classList.add('active');
        document.getElementById('labelTomorrowDate').classList.remove('active');
        document.getElementById('customDataField').classList.remove('active');
        document.getElementById('labelNoneDate').classList.remove('active');
        console.log(`Today: ${yyyy}/${mm}/${dd}`);
        this.finalData = `${yyyy}/${mm}/${dd}`;
      } else {
        document.getElementById('labelTodayDate').classList.remove('active');
      }
    });

    tomorrowDate.addEventListener('click', () => {
      if (!todayDate.checked && tomorrowDate.checked && !customDate.checked && !noneDate.checked) {
        document.getElementById('labelTodayDate').classList.remove('active');
        document.getElementById('labelTomorrowDate').classList.add('active');
        document.getElementById('customDataField').classList.remove('active');
        document.getElementById('labelNoneDate').classList.remove('active');
        console.log(`Tomorrow: ${yyyy}/${mm}/${dd + 1}`);
        this.finalData = `${yyyy}/${mm}/${dd + 1}`;
      } else {
        document.getElementById('labelTomorrowDate').classList.remove('active');
      }
    });

    customDate.addEventListener('click', () => {
      if (!todayDate.checked && !tomorrowDate.checked && customDate.checked && !noneDate.checked) {
        document.getElementById('customDataField').classList.add('active');
        document.getElementById('labelTodayDate').classList.remove('active');
        document.getElementById('labelTomorrowDate').classList.remove('active');
        document.getElementById('labelNoneDate').classList.remove('active');
        this.finalData = `${customDataField.value}`;
      } else {
        document.getElementById('customDataField').classList.remove('active');
      }
    });
    noneDate.addEventListener('click', () => {
      if (!todayDate.checked && !tomorrowDate.checked && !customDate.checked && noneDate.checked) {
        document.getElementById('labelNoneDate').classList.add('active');
        document.getElementById('labelTodayDate').classList.remove('active');
        document.getElementById('labelTomorrowDate').classList.remove('active');
        document.getElementById('customDataField').classList.remove('active');
        this.finalData = '';
      } else {
        document.getElementById('labelNoneDate').classList.remove('active');
      }
    });

    document.getElementById('crearTareaButton').addEventListener('click', () => { this.addTarea(); });
  }

  notaFuncion() {
    document.getElementById('inputTitle').addEventListener('focus', () => {
      document.querySelector('.containerField').classList.add('active');
      document.getElementById('hashtagIcon').classList.add('active');
    });

    document.getElementById('inputTitle').addEventListener('focusout', () => {
      document.querySelector('.containerField').classList.remove('active');
      document.getElementById('hashtagIcon').classList.remove('active');
    });

    document.getElementById('addPersonButton').addEventListener('click', () => {
      document.getElementById('popupAdd').style.display = 'flex';
      this.inputAdd.nativeElement.focus();
    });

    document.getElementById('addPerson').addEventListener('focusout', () => {
      document.getElementById('popupAdd').style.display = 'none';
    });

    document.querySelector('.moreOptions').addEventListener('click', () => {
      document.querySelector('.moreOptions > i').classList.toggle('active');
      document.querySelector('.moreOptionsContainer').classList.toggle('active');

      let texto = document.querySelector('.moreOptions > span');
      texto.textContent === 'Mostrar más opciones' ? texto.textContent = 'Mostrar menos opciones' : texto.textContent = 'Mostrar más opciones';

    });
  }

  removeItem() {
    let listaItems = document.querySelectorAll('.sharePerson > i');
    listaItems.forEach(element => {
      element.addEventListener('click', (e) => {
        let parentNode = (<HTMLElement>(<HTMLElement>e.target).parentNode);
        parentNode.remove();
      });
    });

    /* let index = this.users.indexOf(token);
    if (index > -1) {
      this.users.splice(index, 1);
    } */
  }

  validarDatosAddPerson() {
    let flag = true;
    let lista = document.querySelectorAll('#peopleContainer > .sharePerson');
    if (this.validateEmail(this.formularioAddPerson.value.email)) {
      this.servicio.getUserNote(
        this.formularioAddPerson.value.email,
        JSON.parse(atob(localStorage.getItem('userData'))).email
      ).subscribe(
        (response) => {
          if (response.status) {
            lista.forEach(element => {
              if (response.data.usuario === element.textContent) {
                flag = false;
              }
            });
            if (flag) {
              document.getElementById('popupAdd').style.display = 'none';
              let contenedor = document.getElementById('peopleContainer');

              let item = document.createElement('label');
              item.classList.add('sharePerson');
              item.appendChild(document.createTextNode(response.data.usuario));
              item.style.cssText = "min-width: fit-content; display: flex; align-items: center; font-size: 14px; margin-right: 10px; border: none; background: #70769C; color: #ffffff; border-radius: 30px; cursor: pointer; outline: 0; padding: 3px 6px 3px 4px;";

              let image = document.createElement('img');
              image.style.cssText = " width: 30px; height: 30px; border-radius: 50%; object - fit: cover; margin-right: 6px;";
              image.src = `assets/uploads/${response.data.usuario}/${response.data.imagen}`;
              item.insertBefore(image, item.childNodes[0]);

              let icon = document.createElement('i');
              icon.classList.add('fas', 'fa-times-circle');
              icon.style.cssText = "color: #565B78; font-size: 17px; margin-left: 8px; transition: all .1s;";
              item.append(icon);

              contenedor.insertBefore(item, contenedor.childNodes[0]);
              this.removeItem();


              /* Limpiamos el field */
              this.formularioAddPerson.reset();
            } else {
              this.mensajeNotificacion = 'Vaya! Parece que ya has añadido ese email';
              this.statusNotificacion = 'error';
              this.iconoNotificacion = 'fas fa-exclamation-circle';
              this.ocultarNotificacion(false);
              setTimeout(() => {
                this.ocultarNotificacion(true);
              }, 5000);
            }

          } else {
            this.mensajeNotificacion = response.response;
            this.statusNotificacion = 'error';
            this.iconoNotificacion = 'fas fa-exclamation-circle';
            this.ocultarNotificacion(false);
            setTimeout(() => {
              this.ocultarNotificacion(true);
            }, 5000);
          }

          this.users.push(response.data.token);
        },
        (error) => {
          console.log(error);
        });
    } else {
      this.mensajeNotificacion = 'Por favor, introduce un email válido';
      this.statusNotificacion = 'error';
      this.iconoNotificacion = 'fas fa-exclamation-circle';
      this.ocultarNotificacion(false);
      setTimeout(() => {
        this.ocultarNotificacion(true);
      }, 5000);
    }

  }


  //Extra functions
  validateEmail(email) {
    const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(String(email).toLowerCase());
  }
  ocultarNotificacion(value: boolean) {
    this.mostrarNotificacion = value;
  }

  removeFocus(item) {
    let element = document.getElementById(item);

    document.onclick = function (e) {

      let target = (e && e.target) || (event && event.srcElement);
      let display = 'none';

      while ((<HTMLElement>(<HTMLElement>e.target).parentNode)) {

        if (target == element) {
          display = 'block';
          break;
        }
        target = (<HTMLElement>(<HTMLElement>e.target).parentNode);
      }

      element.style.display = display;

    }
  }

  //Función crear tarea
  addTarea() {
    this.servicio.addTarea(
      this.userdata.token,
      this.formularioAddTarea.value.titulo,
      this.finalData,
      this.formularioAddTarea.value.descripcion
    ).subscribe(
      (response) => {
        console.log(response);
        if (response.status === true) {
          let parent = document.querySelector('#peopleContainer');
          let children = Array.from(document.getElementById('peopleContainer').children);

          children.forEach(element => {
            if (element.classList.contains('sharePerson')) {
              parent.removeChild(element);
            }
          });
          this.formularioAddTarea.reset();
          document.querySelector('.overlayPopupTask').classList.remove('open');

          //Eliminamos las tareas que hayan antes
          let tareas = document.querySelectorAll('.task');

          for (let i = tareas.length - 1; i >= 0; --i) {
            tareas[i].remove();
          }

          this.getDatos(this.userdata.token);
        }
      },
      (error) => {
        console.log(error);
      }
    )
  }

  //Esconder tarea 
  hideTask() {
    let hideOverlay = document.querySelector('.overlayPopupTask');

    window.onclick = function (e) {
      /* if (e.srcElement.className !== "noClose") {
        hideDrop.classList.remove('open');
      } else {
        hideDrop.classList.add('open');
      } */

      if (e.srcElement.className.includes("overlayPopupTask")) {
        hideOverlay.classList.remove('open');
      }
    }

    document.getElementById('closeTaskButton').addEventListener('click', () => {
      hideOverlay.classList.remove('open');
    });
  }

  //Mostrar tarea
  showTask() {
    let hideOverlay = document.querySelector('.overlayPopupTask');
    hideOverlay.classList.add('open');
  }

}
