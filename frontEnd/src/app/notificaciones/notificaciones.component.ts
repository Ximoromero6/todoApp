import { Component, OnInit, Input, Output, EventEmitter } from '@angular/core';

@Component({
  selector: 'app-notificaciones',
  templateUrl: './notificaciones.component.html',
  styleUrls: ['./notificaciones.component.scss']
})
export class NotificacionesComponent implements OnInit {

  constructor() { }

  @Input() mensajeNotificacion: string
  @Output() mostrarNotificacion = new EventEmitter<boolean>();
  iconoNotificacion: string

  ngOnInit(): void {
    this.iconoNotificacion = 'fas fa-exclamation-circle';
  }

  ocultarNotificacion(value: boolean) {
    this.mostrarNotificacion.emit(value);
  }

}
