import { Component, OnInit, Output, EventEmitter } from '@angular/core';
import { FormGroup, FormBuilder, Validators } from '@angular/forms';
import { RegistroService } from './registro.service';
import { Router } from "@angular/router"

@Component({
  selector: 'app-registro',
  templateUrl: './registro.component.html',
  styleUrls: ['./registro.component.scss']
})
export class RegistroComponent implements OnInit {

  //@Output() notificacionHijo: EventEmitter<boolean> = new EventEmitter<boolean>();
  mensajeNotificacion: string
  mostrarNotificacion: boolean = true
  formularioRegistro: FormGroup;

  constructor(
    private formBuilder: FormBuilder,
    private registro: RegistroService,
    private ruta: Router
  ) { }

  ngOnInit(): void {
    this.formularioRegistro = this.formBuilder.group({
      nombre: ['Ximo Romero'],
      usuario: ['Ximoromero5', Validators.compose([Validators.minLength(4), Validators.required])],
      email: ['joaquin@vivaconversion.com', Validators.compose([Validators.email, Validators.required])],
      clave: ['123456', Validators.compose([Validators.minLength(6), Validators.required])]
    });
  }

  validarDatosRegistro() {
    if (this.formularioRegistro.invalid) {
      this.formularioRegistro.get("nombre").markAsDirty();
      this.formularioRegistro.get("usuario").markAsDirty();
      this.formularioRegistro.get("email").markAsDirty();
      this.formularioRegistro.get("clave").markAsDirty();
      return;
    }

    console.log(JSON.stringify(this.formularioRegistro.value));

    this.registro.registrarUsuario(this.formularioRegistro.value.nombre, this.formularioRegistro.value.usuario, this.formularioRegistro.value.email, this.formularioRegistro.value.clave).subscribe(
      (response) => {
        console.log(response);
        if (response.status == 1) {
          //Redireccionar a página verificar cuenta pero si hay un error se muestra
          localStorage.setItem('userToken', response.data.token);
          this.ruta.navigate(['/registroFinal']);
        } else {
          this.mensajeNotificacion = response.response;
          this.ocultarNotificacion(false);
        }
      },
      error => {
        console.log(error);
      });
  }

  ocultarNotificacion(value: boolean) {
    this.mostrarNotificacion = value;
  }

}
