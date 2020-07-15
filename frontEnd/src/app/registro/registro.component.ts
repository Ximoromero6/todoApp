import { Component, OnInit, Input } from '@angular/core';
import { FormGroup, FormBuilder, Validators } from '@angular/forms';
import { RegistroService } from './registro.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-registro',
  templateUrl: './registro.component.html',
  styleUrls: ['./registro.component.scss'],
})
export class RegistroComponent implements OnInit {

  //Notificaciones variables
  statusNotificacion: string;
  iconoNotificacion: string;
  mensajeNotificacion: string;
  mostrarNotificacion: boolean = true;

  formularioRegistro: FormGroup;
  showLoader: boolean = true;

  constructor(
    private formBuilder: FormBuilder,
    private registro: RegistroService,
    private ruta: Router
  ) { }

  ngOnInit(): void {
    this.formularioRegistro = this.formBuilder.group({
      nombre: [''],
      usuario: [
        '',
        Validators.compose([Validators.minLength(4), Validators.required]),
      ],
      email: ['', Validators.compose([Validators.email, Validators.required])],
      clave: [
        '',
        Validators.compose([Validators.minLength(6), Validators.required]),
      ],
    });
  }

  validarDatosRegistro() {
    if (this.formularioRegistro.invalid) {
      this.formularioRegistro.get('nombre').markAsDirty();
      this.formularioRegistro.get('usuario').markAsDirty();
      this.formularioRegistro.get('email').markAsDirty();
      this.formularioRegistro.get('clave').markAsDirty();
      return;
    } else {
      this.showLoader = false;
    }

    console.log(JSON.stringify(this.formularioRegistro.value));

    this.registro
      .registrarUsuario(
        this.formularioRegistro.value.nombre,
        this.formularioRegistro.value.usuario,
        this.formularioRegistro.value.email,
        this.formularioRegistro.value.clave
      )
      .subscribe(
        (response) => {
          console.log(response);
          if (response.status == 1) {
            //Redireccionar a página verificar cuenta pero si hay un error se muestra
            localStorage.setItem('userToken', response.data.token);
            this.ruta.navigateByUrl('/registroFinal', { state: { email: this.formularioRegistro.value.email } });
          } else {
            this.statusNotificacion = 'error';
            this.iconoNotificacion = 'fas fa-exclamation-circle';
            this.mensajeNotificacion = response.response;
            this.ocultarNotificacion(false);
          }
        },
        (error) => {
          console.log(error);
        }
      );
  }

  ocultarNotificacion(value: boolean) {
    this.mostrarNotificacion = value;
  }
}
