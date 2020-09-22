import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { NotaComponent } from '../nota/nota.component';
import { DashBoardServiceService } from './dash-board-service.service';
import { FormGroup, FormBuilder, Validators } from '@angular/forms';
import { MatDatepickerInputEvent } from '@angular/material/datepicker';
import { DomSanitizer } from '@angular/platform-browser';
import { SharedDashboardHeaderServiceService } from '../home/shared-dashboard-header-service.service';
import { Subscription } from 'rxjs';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.scss']
})
export class DashboardComponent implements OnInit {

  data: any;
  todayTaskCount: number = 0;
  tomorrowTaskCount: number = 0;
  nextTaskCount: number = 0;

  //Array con todas las tareas
  listaTareas = [];

  listaTareasToday = [];
  listaTareasTomorrow = [];
  listaTareasNext = [];

  //Array para saber la tarea seleccionada
  tareaSeleccionada;
  show = false;

  //Formulario para añadir un comentario
  formularioAddComentario: FormGroup;

  //Formulario para añadir un comentario
  formularioAddParticipante: FormGroup;

  //Formulario para cambiar el creador de la tarea
  formularioCambiarCreador: FormGroup;

  //Miembros del equipo
  miembros;

  //Montamos la imágen
  imagen;

  subscription: Subscription;

  date = new Date();
  today = `${this.date.getFullYear()}-${("0" + (this.date.getMonth() + 1)).slice(-2)}-${("0" + (this.date.getUTCDate())).slice(-2)}`;
  tomorrow = `${this.date.getFullYear()}-${("0" + (this.date.getMonth() + 1)).slice(-2)}-${("0" + (this.date.getUTCDate() + 1)).slice(-2)}`;

  constructor(
    private servicio: DashBoardServiceService,
    private formBuilder: FormBuilder,
    private sanitizer: DomSanitizer,
    private SharedDashboardHeaderServiceService: SharedDashboardHeaderServiceService
  ) {
    this.data = localStorage.getItem('userData') != null ? JSON.parse(atob(localStorage.getItem('userData'))) : JSON.parse(atob(sessionStorage.getItem('userData')));
    this.subscription = SharedDashboardHeaderServiceService.task$.subscribe(
      (response) => {

        //Asignamos las tareas
        this.listaTareasToday = this.listaTareas.filter(e => e.fecha == this.today || e.fecha < this.today);
        /* this.SharedDashboardHeaderServiceService.updateTasks(this.listaTareasToday); */

        //Asignamos el número de tareas de hoy
        this.todayTaskCount = this.listaTareasToday.length;

        //añadimos las tareas de mañana a un array
        this.listaTareasTomorrow = this.listaTareas.filter(e => e.fecha == this.tomorrow);

        //Asignamos el número de tareas de mañana
        this.tomorrowTaskCount = this.listaTareasTomorrow.length;

        //Asignamos las próximas tareas al array next
        this.listaTareasNext = this.listaTareas.filter(e => e.fecha > this.tomorrow);

        //Asignamos el número de tareas próximas
        this.nextTaskCount = this.listaTareasNext.length;
      },
      (error) => {
        console.error(error);
      }
    );
  }

  @ViewChild(NotaComponent) child;

  dateChanged(event: MatDatepickerInputEvent<Date>, idTask) {
    let date = new Date((event.targetElement as HTMLInputElement).value);
    let finalDate = `${date.getFullYear()}-${("0" + (date.getMonth() + 1)).slice(-2)}-${("0" + (date.getUTCDate() + 1)).slice(-2)}`;

    this.servicio.changeTaskDate(idTask, finalDate).subscribe(
      (response) => {
        if (response.status) {
          ((event.targetElement as HTMLInputElement).previousSibling.childNodes[1]).textContent = finalDate;
        }
      },
      (error) => {
        console.error(error);
      }
    );
  }

  ngOnInit(): void {
    this.obtenerTareas(this.data.token);
    this.addTarea();
    this.init();

    let animateButton = function (e) {

      e.preventDefault;
      //reset animation
      e.target.classList.remove('animate');

      e.target.classList.add('animate');
      setTimeout(function () {
        e.target.classList.remove('animate');
      }, 600);
    };

    this.formularioAddComentario = this.formBuilder.group({
      comentario: ['', Validators.required]
    });

    this.formularioAddParticipante = this.formBuilder.group({
      usuario: ['', Validators.required]
    });

    this.formularioCambiarCreador = this.formBuilder.group({
      usuario: ['', Validators.required]
    });
  }

  completed(idTarea) {
    this.servicio.completarTarea(idTarea).subscribe(
      (response) => {
        if (response.status) {
          let complete: any = document.getElementById('completedButton');
          let icon = document.createElement('i');
          icon.classList.add('fas', 'fa-check');
          icon.style.marginLeft = '6px';

          complete.style.backgroundColor = '#00bf9c';
          complete.style.color = '#ffffff';
          complete.textContent = 'Tarea completada!';
          complete.appendChild(icon);
        }
      },
      (error) => {
        console.error(error);
      }
    );
  }

  completeLeftButton(id, target: HTMLElement): void {
    let el: HTMLElement | null = target;
    let task = (<Element>el.parentNode.parentNode);
    task.classList.add('animate__animated', 'animate__zoomOutLeft'); //animate__bounceOutLeft

    task.addEventListener('animationend', () => {
      //Llamamos al servicio
      this.servicio.completarTarea(id).subscribe(
        (response) => {
          this.obtenerTareas(this.data.token);
          if (response.status) {
            task.remove();
            this.SharedDashboardHeaderServiceService.updateTasks(this.listaTareas);
          }
        },
        (error) => { console.log(error) }
      );

    });


  }

  addTarea() {
    document.getElementById('addTareaButton').addEventListener('click', () => {
      this.child.showTask();
      this.child.hideTask();
    });
  }

  init() {

    //Today task container
    document.querySelector('#today > .top').addEventListener('click', () => {
      this.show = false;
      document.querySelector('#today > .top > i').classList.toggle('active');
      document.querySelector('#today > .bottom').classList.toggle('active');

      //Remove tomorrow
      document.querySelector('#tomorrow > .top > i').classList.remove('active');
      document.querySelector('#tomorrow > .bottom').classList.remove('active');

      //Remove next
      document.querySelector('#next > .top > i').classList.remove('active');
      document.querySelector('#next > .bottom').classList.remove('active');

    });

    //Tomorrow task container
    document.querySelector('#tomorrow > .top').addEventListener('click', () => {
      this.show = false;
      document.querySelector('#tomorrow > .top > i').classList.toggle('active');
      document.querySelector('#tomorrow > .bottom').classList.toggle('active');

      //Remove today
      document.querySelector('#today > .top > i').classList.remove('active');
      document.querySelector('#today > .bottom').classList.remove('active');

      //Remove next
      document.querySelector('#next > .top > i').classList.remove('active');
      document.querySelector('#next > .bottom').classList.remove('active');

    });

    //Next task container
    document.querySelector('#next > .top').addEventListener('click', () => {
      this.show = false;
      document.querySelector('#next > .top > i').classList.toggle('active');
      document.querySelector('#next > .bottom').classList.toggle('active');

      //Remove today
      document.querySelector('#today > .top > i').classList.remove('active');
      document.querySelector('#today > .bottom').classList.remove('active');

      //Remove tomorrow
      document.querySelector('#tomorrow > .top > i').classList.remove('active');
      document.querySelector('#tomorrow > .bottom').classList.remove('active');

    });

    let texto = document.querySelector('.moreOptions > span');
    texto.textContent === 'Mostrar más opciones' ? texto.textContent = 'Mostrar menos opciones' : texto.textContent = 'Mostrar más opciones';

    //Paste
    document.addEventListener('paste', function (e) {
      e.preventDefault();

      let pastedText = '';
      let clipboardData = (<any>window).clipboardData;
      console.log(clipboardData);

      /* if (clipboardData && clipboardData.getData) {
        pastedText = clipboardData.getData('Text');
      } else if (e.clipboardData && e.clipboardData.getData) {
        pastedText = e.clipboardData.getData('text/html');
      }

      document.getElementById('target').innerHTML = pastedText */
    });
  }

  obtenerTareas(userToken) {
    this.servicio.obtenerTareas(userToken).subscribe(
      (response) => {
        console.log('b');
        if (response.status) {
          this.listaTareas = response.tasks;
          this.SharedDashboardHeaderServiceService.updateTasks(this.listaTareas);
        }
      },
      (error) => {
        console.log(error);
      });
  }

  ampliarTarea(tarea, evt) {
    let el = evt;
    if (el.target.classList.value === 'task') {
      this.tareaSeleccionada = tarea;
      console.log(this.tareaSeleccionada);
      this.show = true;
    }
  }

  //Función para eliminar las tareas
  eliminarTarea(id) {
    if (confirm('¿Eliminar tarea?')) {
      this.servicio.eliminarTarea(id).subscribe((response) => {
        if (response.status) {
          this.obtenerTareas(this.data.token);
        }
      }, (error) => {
        console.log(error)
      });
      this.show = false;
    }
  }

  //Función para añadir comentarios
  insertarComentario(idTarea) {
    let comentario = this.formularioAddComentario.get('comentario').value;
    if (comentario != '' && comentario != null) {
      this.servicio.addComentario(this.data.token, idTarea, comentario).subscribe(
        (response) => {
          console.log(response);
          if (response.status) {
            this.formularioAddComentario.reset();
            let date = new Date();

            let today = `${date.getFullYear()}-${("0" + (date.getMonth() + 1)).slice(-2)}-${("0" + (date.getUTCDate())).slice(-2)}`;
            let aux = {
              "id": response.response,
              "imagen": this.data.imagen,
              "usuario": this.data.usuario,
              "comentario": comentario,
              "fecha": today
            }
            this.tareaSeleccionada.comentarios.push(aux);
            this.SharedDashboardHeaderServiceService.updateTasks(this.listaTareas);
          }
        },
        (error) => {
          console.log(error);
        }
      );
    }
  }

  //Función para mostrar en segundos, minutos, horas, días la fecha desde que se comentó
  obtenerFecha(fecha) {
    /* alert(fecha); */
    let hoy = new Date(fecha);
    alert(this.dateDiffInDays(hoy, fecha));
  }

  dateDiffInDays(a, b) {
    const _MS_PER_DAY = 1000 * 60 * 60 * 24;
    const utc1 = Date.UTC(a.getFullYear(), a.getMonth(), a.getDate());
    const utc2 = Date.UTC(b.getFullYear(), b.getMonth(), b.getDate());

    return Math.floor((utc2 - utc1) / _MS_PER_DAY);
  }

  open(evt) {
    document.querySelector('.contenedorAddPersonas').classList.add('active');
    document.getElementById('personasAsignadas').classList.add('active');
  }

  agregarParticipanteExtra(idTarea) {
    if (this.formularioAddParticipante.get('usuario').value != '') {
      this.servicio.agregarParticipante(this.data.token, idTarea, this.formularioAddParticipante.get('usuario').value).subscribe(
        (response) => {
          console.log(response);
          if (response.status) {
            this.formularioAddParticipante.reset();

            let aux = {
              "id": response.response.id,
              "imagen": response.response.imagen,
              "usuario": response.response.usuario
            }
            this.tareaSeleccionada.colaboradores.push(aux);

            document.getElementById('contenedorAddPersonas').classList.remove('active');
            document.getElementById('personasAsignadas').classList.remove('active');

          }
        },
        (error) => {
          console.log(error);
        });
    }
  }

  eliminarParticipanteExtra(idTarea, idUsuario) {
    let parent = (<HTMLElement>(<HTMLElement>event.target).parentNode);
    this.servicio.eliminarParticipanteExtra(idTarea, idUsuario).subscribe(
      (response) => {
        if (response.status) {
          parent.remove();
        }
      },
      (error) => {
        console.log(error);
      }
    );
  }
  closePopup() {
    document.getElementById('contenedorAddPersonas').classList.remove('active');
    document.getElementById('personasAsignadas').classList.remove('active');
  }

  editarTitulo(idTarea) {
    let texto = (<HTMLElement>event.target).textContent;
    this.servicio.editarTitulo(idTarea, texto).subscribe(
      (response) => {
        if (response.status) {
          console.log(response);
        }
      },
      (error) => {
        console.error(error);
      }
    );
  }

  editarDescripcion(idTarea) {

    /* let editor = document.getElementById("descripcionTarea");
    editor.addEventListener("paste", function (e) {
      e.preventDefault();
      let text = e.clipboardData.getData('text/plain');
      document.execCommand("insertHTML", false, text);
    }); */

    let texto = (<HTMLElement>event.target).textContent;
    this.servicio.editarDescripcion(idTarea, texto).subscribe(
      (response) => {
        if (response.status) {
          console.log(response);
        }
      },
      (error) => {
        console.error(error);
      }
    );
  }

  eliminarComentario(evt, idTarea, idComentario) {
    this.servicio.eliminarComentario(idTarea, idComentario).subscribe(
      (response) => {
        if (response.status) {
          evt.target.parentNode.parentNode.remove();
        }
      },
      (error) => {
        console.error(error);
      }
    );
  }

  //Función para devolver si la fecha es hoy en letras, mañana etc
  returnDate(fecha) {
    let date = new Date();
    let today = `${date.getFullYear()}-${("0" + (date.getMonth() + 1)).slice(-2)}-${("0" + (date.getUTCDate())).slice(-2)}`;
    let tomorrow = `${date.getFullYear()}-${("0" + (date.getMonth() + 1)).slice(-2)}-${("0" + (date.getUTCDate() + 1)).slice(-2)}`;

    if (fecha == today) {
      return "Hoy";
    }
    else if (fecha == tomorrow) {
      return "Mañana";
    } else if (fecha == '0000-00-00') {
      return "Sin fecha de entrega";
    } else {
      return fecha;
    }
  }

  //Obtener tdooa loa participantes del equipo para seleccionar alguno
  getInfoUsersTeam() {
    this.servicio.obtenerDatosEquipo2(this.data.token).subscribe(
      (response) => {
        if (response.status) {
          document.getElementById('creador').classList.add('active');
          document.getElementById('contenedorCambiarCreador').classList.add('active');
          this.miembros = response.response;
        }
      },
      (error) => {
        console.error(error);
      }
    );
  }

  //Función que reemplaza al creador
  replaceCreator(idTarea, oldCreatorName, idSustituto, newUser, newImagen) {
    this.servicio.replaceCreator(idTarea, oldCreatorName, idSustituto).subscribe(
      (response) => {
        if (response.status) {
          document.getElementById('contenedorCambiarCreador').classList.remove('active');
          //(document.querySelector('#creador > img') as HTMLImageElement).src = `../../assets/uploads/${newUser}/${newImagen}`;
          //document.querySelector('#creador > p').textContent = newUser;

          this.tareaSeleccionada.imagen = newImagen;
          this.tareaSeleccionada.usuario = newUser;

        }
      },
      (error) => {
        console.error(error);
      }
    );

  }

  //Función para eliminar la fecha de entrega
  deleteTaskDate(evt, idTarea) {
    this.servicio.deleteTaskDate(idTarea).subscribe(
      (response) => {
        if (response.status) {
          evt.target.previousSibling.textContent = "Sin fecha de entrega";
        }
      },
      (error) => {
        console.error(error);
      }
    );
  }

  //Función para mostrar las X cuando haces hover en una imágen de un usuario participante
  showCross(evt) {
    evt.target.nextSibling.style.display = 'flex';
  }

  hideCross(evt) {
    evt.target.style.display = 'none';
  }
}
