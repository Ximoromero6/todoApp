import { Component, OnInit, ViewChild } from '@angular/core';
import { NotaComponent } from '../nota/nota.component';
import { DashBoardServiceService } from './dash-board-service.service';
import { JsonPipe } from '@angular/common';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.scss']
})
export class DashboardComponent implements OnInit {

  data: any;
  todayTaskCount: number = 0;
  tomorrowTaskCount: number = 0;
  futureTaskCount: number = 0;

  //Array con todas las tareas
  listaTareasToday = [];
  listaTareasTomorrow;

  //Array para saber la tarea seleccionada
  tareaSeleccionada;
  show = false;

  constructor(
    private servicio: DashBoardServiceService
  ) {
    this.data = localStorage.getItem('userData') != null ? JSON.parse(atob(localStorage.getItem('userData'))) : JSON.parse(atob(sessionStorage.getItem('userData')));
  }

  @ViewChild(NotaComponent) child;

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

    let completedButton: any = document.getElementsByClassName("completedButton");

    for (let i = 0; i < completedButton.length; i++) {
      completedButton[i].addEventListener('click', animateButton, false);
    }

  }

  completed() {
    let complete: any = document.getElementById('completedButton');
    let icon = document.createElement('i');
    icon.classList.add('fas', 'fa-check');
    icon.style.marginLeft = '6px';

    complete.style.backgroundColor = '#00bf9c';
    complete.style.color = '#ffffff';
    complete.textContent = 'Tarea completada!';
    complete.appendChild(icon);
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
  }

  obtenerTareas(userToken) {
    this.servicio.obtenerTareas(userToken).subscribe(
      (response) => {
        if (response.status) {
          let date = new Date();
          let today = `${date.getFullYear()}-${("0" + (date.getMonth() + 1)).slice(-2)}-${("0" + (date.getUTCDate())).slice(-2)}`;
          let tomorrow = `${date.getFullYear()}-${("0" + (date.getMonth() + 1)).slice(-2)}-${("0" + (date.getUTCDate() + 1)).slice(-2)}`;

          //Asignamos las tareas
          this.listaTareasToday = response.tasks.filter(e => e.fecha == today);

          //Asignamos el número de tareas de hoy
          this.todayTaskCount = this.listaTareasToday.length;

          //añadimos las tareas de mañana a un array
          this.listaTareasTomorrow = response.tasks.filter(e => e.fecha == tomorrow);

          //Asignamos el número de tareas de mañana
          this.tomorrowTaskCount = this.listaTareasTomorrow.length;

        }
      },
      (error) => {
        console.log(error);
      });
  }

  ampliarTarea(tarea) {
    this.tareaSeleccionada = tarea;
    /* let container = document.querySelector('.containerTasks') as HTMLElement;
    container.style.flexBasis = "50%"; */
    this.show = true;
  }

  setUrl() {
    let url = (<HTMLTextAreaElement>document.getElementById("descripcionTarea")).value;
    let sText = document.getSelection();
    document.execCommand('insertHTML', false, '<a href="' + url + '" target="_blank">' + sText + '</a>');
    url = '';
  }

  //Función para eliminar las tareas
  eliminarTarea(token, id) {
    if (confirm('¿Eliminar tarea?')) {
      this.servicio.eliminarTarea(token, id).subscribe((response) => { console.log(response) }, (error) => { console.log(error) });
      this.show = false;
      this.obtenerTareas(this.data.token);
    }
  }

}
