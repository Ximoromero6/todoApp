import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
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

  @ViewChild('test') test: ElementRef;
  @ViewChild('inputTitle') inputTitle: ElementRef;

  constructor(
    private formBuilder: FormBuilder,
    private servicio: ServicioService
  ) { }

  ngOnInit(): void {
    this.userdata = localStorage.getItem('userData') != null ? JSON.parse(atob(localStorage.getItem('userData'))) : JSON.parse(atob(sessionStorage.getItem('userData')));
    this.users.push(this.userdata.usuario);
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

    /* this.inputTitle.nativeElement.focus(); */
    this.notaFuncion();
    // this.removeItem();

    //Obtener fecha seleccionada
    let todayDate: any = document.getElementById('todayDate');
    let tomorrowDate: any = document.getElementById('tomorrowDate');
    let customDate: any = document.getElementById('customDate');
    let customDateField: any = document.getElementById('customDateField');

    let today: any = new Date();
    let dd: number = today.getDate();
    let mm: number = today.getMonth() + 1;
    const yyyy = today.getFullYear();

    this.finalData = `${yyyy}/${mm}/${dd}`;

    todayDate.addEventListener('click', () => {
      if (todayDate.checked && !tomorrowDate.checked && !customDate.checked) {
        document.getElementById('labelTodayDate').classList.add('active');
        document.getElementById('labelTomorrowDate').classList.remove('active');
        document.getElementById('labelCustomDate').classList.remove('active');
        console.log(`Today: ${yyyy}/${mm}/${dd}`);
        this.finalData = `${yyyy}/${mm}/${dd}`;
      } else {
        document.getElementById('labelTodayDate').classList.remove('active');
      }
    });

    tomorrowDate.addEventListener('click', () => {
      if (!todayDate.checked && tomorrowDate.checked && !customDate.checked) {
        document.getElementById('labelTodayDate').classList.remove('active');
        document.getElementById('labelTomorrowDate').classList.add('active');
        document.getElementById('labelCustomDate').classList.remove('active');
        console.log(`Tomorrow: ${yyyy}/${mm}/${dd + 1}`);
        this.finalData = `${yyyy}/${mm}/${dd + 1}`;
      } else {
        document.getElementById('labelTomorrowDate').classList.remove('active');
      }
    });

    customDate.addEventListener('click', () => {
      if (!todayDate.checked && !tomorrowDate.checked && customDate.checked) {
        document.getElementById('labelCustomDate').classList.add('active');
        document.getElementById('labelTodayDate').classList.remove('active');
        document.getElementById('labelTomorrowDate').classList.remove('active');
        document.getElementById('labelCustomDate').classList.add('active');
        console.log(`Custom date: `);
        //  finalData = `${customDateField.value}`;
        this.finalData = "Custom";
      } else {
        document.getElementById('labelCustomDate').classList.remove('active');
      }
    });

    document.getElementById('crearTareaButton').addEventListener('click', () => { this.addTarea(); });
  }

  notaFuncion() {
    document.getElementById('inputTitle').addEventListener('focus', () => {
      document.querySelector('.containerField').classList.add('active');
    });

    document.getElementById('inputTitle').addEventListener('focusout', () => {
      document.querySelector('.containerField').classList.remove('active');
    });

    document.getElementById('addPersonButton').addEventListener('click', () => {
      document.getElementById('popupAdd').style.display = 'flex';
      this.test.nativeElement.focus();
    });

    document.querySelector('.moreOptions').addEventListener('click', () => {
      document.querySelector('.moreOptions > i').classList.toggle('active');
      document.querySelector('.moreOptionsContainer').classList.toggle('active');
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
              item.style.cssText = "min-width: fit-content; display: flex; align-items: center; font-size: 14px; margin-right: 10px; border: none; background: #70769C; color: #ffffff; border-radius: 5px; cursor: pointer; outline: 0; padding: 8px 12px;";

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

          this.users.push(response.data.usuario);
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
    var element = document.getElementById(item);

    document.onclick = function (e) {

      var target = (e && e.target) || (event && event.srcElement);
      var display = 'none';

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
  //Función crear tareaw
  addTarea() {
    let formData: FormData = new FormData();

    formData.append('titulo', this.formularioAddTarea.value.titulo);
    formData.append('fecha', this.finalData);
    formData.append('descripcion', this.formularioAddTarea.value.descripcion);
    formData.append('usuarios', JSON.stringify(this.users));

    //Llamar al servicio (CREAR SERVICIO NOTA)

  }
}
